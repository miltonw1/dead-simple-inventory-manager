<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);

    config()->set('services.mercado_pago.access_token', 'test-token');
    config()->set('services.mercado_pago.base_url', 'https://api.mercadopago.com');
    config()->set('services.mercado_pago.back_url', 'https://app.test/subscription/return');
    config()->set('services.mercado_pago.webhook_url', 'https://app.test/api/webhooks/mercado-pago');
    config()->set('services.mercado_pago.payer_email_override', null);
    config()->set('services.mercado_pago.plans.monthly.amount', 9990);
    config()->set('services.mercado_pago.plans.monthly.currency', 'ARS');
});


test('user can create mercado pago subscription checkout', function () {
    Http::fake([
        'api.mercadopago.com/checkout/preferences' => Http::response([
            'id' => 'preapproval-123',
            'init_point' => 'https://www.mercadopago.com.ar/subscriptions/checkout',
        ], 201),
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->postJson('api/subscription/checkout', [
            'plan' => 'monthly',
        ])
        ->assertStatus(201)
        ->assertJsonPath('checkout_url', 'https://www.mercadopago.com.ar/subscriptions/checkout')
        ->assertJsonPath('subscription.provider', 'mercado_pago')
        ->assertJsonPath('subscription.provider_subscription_id', 'preapproval-123')
        ->assertJsonPath('subscription.status', 'pending');

    $subscription = $response->json('subscription');

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $this->user->id,
        'status' => 'pending',
        'provider' => 'mercado_pago',
        'provider_subscription_id' => 'preapproval-123',
        'external_reference' => $subscription['external_reference'],
        'plan' => 'monthly',
        'amount' => 9990,
        'currency' => 'ARS',
        'last_payment_status' => 'pending',
    ]);

    Http::assertSent(function ($request) use ($subscription) {
        \Illuminate\Support\Facades\Log::info("SENT URL: " . $request->url(), [
            'body' => $request->data(),
        ]);
        return $request->url() === 'https://api.mercadopago.com/checkout/preferences'
            && $request['external_reference'] === $subscription['external_reference']
            && $request['payer']['email'] === $this->user->email
            && (float) $request['items'][0]['unit_price'] === 9990.0
            && $request['items'][0]['currency_id'] === 'ARS';
    });
});



test('webhook activates pending subscription with day accumulation', function () {
    config()->set('services.mercado_pago.plans.monthly.days', 30);

    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'pending',
        'provider' => 'mercado_pago',
        'plan' => 'monthly',
        'amount' => 9990,
        'external_reference' => 'subscription:activation-test',
        'starts_at' => now(),
        'ends_at' => now(),
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/11111' => Http::response([
            'id' => 11111,
            'external_reference' => 'subscription:activation-test',
            'status' => 'approved',
        ], 200),
    ]);

    $this->postJson('/api/webhooks/mercado-pago', [
        'type' => 'payment',
        'data' => ['id' => '11111'],
    ])->assertStatus(200)->assertJson(['ok' => true]);

    $subscription->refresh();

    expect($subscription->status)->toBe('active');
    expect($subscription->last_payment_status)->toBe('approved');
    expect($subscription->provider_payment_id)->toBe('11111');
    expect(now()->diffInDays($subscription->ends_at))->toBeGreaterThanOrEqual(29);
});

test('webhook accumulates days on active subscription', function () {
    config()->set('services.mercado_pago.plans.monthly.days', 30);

    $endsAt = now()->addDays(10);

    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'provider' => 'mercado_pago',
        'plan' => 'monthly',
        'amount' => 9990,
        'external_reference' => 'subscription:accumulation-test',
        'starts_at' => now()->subDays(20),
        'ends_at' => $endsAt,
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/22222' => Http::response([
            'id' => 22222,
            'external_reference' => 'subscription:accumulation-test',
            'status' => 'approved',
        ], 200),
    ]);

    $this->postJson('/api/webhooks/mercado-pago', [
        'type' => 'payment',
        'data' => ['id' => '22222'],
    ])->assertStatus(200)->assertJson(['ok' => true]);

    $subscription->refresh();

    expect($subscription->last_payment_status)->toBe('approved');
    expect($subscription->provider_payment_id)->toBe('22222');
    expect(now()->diffInDays($subscription->ends_at))->toBeGreaterThanOrEqual(39);
});

test('webhook ignores non-approved payments', function () {
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'pending',
        'provider' => 'mercado_pago',
        'plan' => 'monthly',
        'external_reference' => 'subscription:rejected-test',
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/33333' => Http::response([
            'id' => 33333,
            'external_reference' => 'subscription:rejected-test',
            'status' => 'rejected',
        ], 200),
    ]);

    $this->postJson('/api/webhooks/mercado-pago', [
        'type' => 'payment',
        'data' => ['id' => '33333'],
    ])->assertStatus(200)->assertJson(['ok' => true]);

    $subscription->refresh();

    expect($subscription->status)->toBe('pending');
    expect($subscription->last_payment_status)->toBe('rejected');
});

test('webhook returns ok even when subscription not found', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments/44444' => Http::response([
            'id' => 44444,
            'external_reference' => 'subscription:nonexistent',
            'status' => 'approved',
        ], 200),
    ]);

    $this->postJson('/api/webhooks/mercado-pago', [
        'type' => 'payment',
        'data' => ['id' => '44444'],
    ])->assertStatus(200)->assertJson(['ok' => true]);
});

test('checkout requires configured plan amount', function () {
    Http::fake();
    config()->set('services.mercado_pago.plans.monthly.amount', 0);

    $this->actingAs($this->user, 'api')
        ->postJson('api/subscription/checkout', [
            'plan' => 'monthly',
        ])
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Subscription plan [monthly] must have an amount greater than zero.',
        ]);

    Http::assertNothingSent();
});
