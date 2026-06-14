<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);

    config()->set('services.mercado_pago.access_token', 'test-token');
    config()->set('services.mercado_pago.base_url', 'https://api.mercadopago.com');
    config()->set('services.mercado_pago.back_url', 'https://app.test/subscription/return');
    config()->set('services.mercado_pago.webhook_url', 'https://app.test/api/webhooks/mercado-pago');
    config()->set('services.mercado_pago.plans.monthly.amount', 9990);
    config()->set('services.mercado_pago.plans.monthly.currency', 'ARS');
});

test('user can create mercado pago subscription checkout', function () {
    Http::fake([
        'api.mercadopago.com/preapproval' => Http::response([
            'id' => 'preapproval-123',
            'status' => 'pending',
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
        return $request->url() === 'https://api.mercadopago.com/preapproval'
            && $request['external_reference'] === $subscription['external_reference']
            && $request['payer_email'] === $this->user->email
            && $request['auto_recurring']['transaction_amount'] === 9990.0
            && $request['auto_recurring']['currency_id'] === 'ARS';
    });
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
