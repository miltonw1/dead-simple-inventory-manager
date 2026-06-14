<?php

use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(UserSeeder::class);
    $this->adminUser = User::where('role', 'admin')->first();
    $this->normalUser = User::factory()->create(['role' => 'user']);
});

test('retrieve subscription status for user with no subscription', function () {
    $this->actingAs($this->normalUser, 'api')
        ->getJson('api/user/subscription')
        ->assertStatus(200)
        ->assertJson([
            'has_active_subscription' => false,
            'subscription' => null,
        ]);
});

test('retrieve subscription status for user with active subscription', function () {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->normalUser->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    $this->actingAs($this->normalUser, 'api')
        ->getJson('api/user/subscription')
        ->assertStatus(200)
        ->assertJson([
            'has_active_subscription' => true,
        ])
        ->assertJsonPath('subscription.uuid', (string) $subscription->uuid);
});

test('retrieve subscription status for user with expired subscription', function () {
    $subscription = Subscription::factory()->expired()->create([
        'user_id' => $this->normalUser->id,
    ]);

    $this->actingAs($this->normalUser, 'api')
        ->getJson('api/user/subscription')
        ->assertStatus(200)
        ->assertJson([
            'has_active_subscription' => false,
        ])
        ->assertJsonPath('subscription.uuid', (string) $subscription->uuid);
});

test('admin can create subscription', function () {
    $data = [
        'user_id' => $this->normalUser->id,
        'status' => 'active',
        'starts_at' => now()->toIso8601String(),
        'ends_at' => now()->addMonth()->toIso8601String(),
    ];

    $this->actingAs($this->adminUser, 'api')
        ->postJson('api/admin/subscriptions', $data)
        ->assertStatus(201)
        ->assertJsonStructure([
            'uuid',
            'user_id',
            'status',
            'starts_at',
            'ends_at',
        ]);

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $this->normalUser->id,
        'status' => 'active',
    ]);
});

test('non-admin cannot create subscription', function () {
    $data = [
        'user_id' => $this->normalUser->id,
        'status' => 'active',
        'starts_at' => now()->toIso8601String(),
        'ends_at' => now()->addMonth()->toIso8601String(),
    ];

    $this->actingAs($this->normalUser, 'api')
        ->postJson('api/admin/subscriptions', $data)
        ->assertStatus(403);
});

test('admin can update subscription payment fields', function () {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->normalUser->id,
        'status' => 'active',
    ]);

    $data = [
        'status' => 'cancelled',
        'provider' => 'mercado_pago',
        'provider_subscription_id' => 'preapproval-123',
        'provider_payment_id' => 'payment-456',
        'external_reference' => 'subscription-'.$subscription->uuid,
        'plan' => 'monthly',
        'amount' => 9990,
        'currency' => 'ARS',
        'last_payment_status' => 'approved',
        'cancelled_at' => now()->toIso8601String(),
    ];

    $this->actingAs($this->adminUser, 'api')
        ->patchJson("api/admin/subscriptions/{$subscription->uuid}", $data)
        ->assertStatus(200)
        ->assertJsonPath('status', 'cancelled')
        ->assertJsonPath('provider', 'mercado_pago')
        ->assertJsonPath('provider_subscription_id', 'preapproval-123')
        ->assertJsonPath('plan', 'monthly');

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => 'cancelled',
        'provider' => 'mercado_pago',
        'provider_subscription_id' => 'preapproval-123',
        'provider_payment_id' => 'payment-456',
        'external_reference' => 'subscription-'.$subscription->uuid,
        'plan' => 'monthly',
        'amount' => 9990,
        'currency' => 'ARS',
        'last_payment_status' => 'approved',
    ]);
});

test('non-admin cannot update subscription', function () {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->normalUser->id,
    ]);

    $this->actingAs($this->normalUser, 'api')
        ->patchJson("api/admin/subscriptions/{$subscription->uuid}", [
            'status' => 'cancelled',
        ])
        ->assertStatus(403);
});

test('user with active subscription can perform write operations on inventory', function () {
    Subscription::factory()->create([
        'user_id' => $this->normalUser->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    $data = ['name' => 'Active Sub Cat', 'user_id' => $this->normalUser->id];

    $this->actingAs($this->normalUser, 'api')
        ->postJson('api/categories', $data)
        ->assertStatus(201);

    $this->assertDatabaseHas('categories', [
        'name' => 'Active Sub Cat',
        'user_id' => $this->normalUser->id,
    ]);
});

test('user with inactive or expired subscription gets 403 on write operations on inventory', function () {
    // expired subscription
    Subscription::factory()->expired()->create([
        'user_id' => $this->normalUser->id,
    ]);

    $data = ['name' => 'Expired Sub Cat', 'user_id' => $this->normalUser->id];

    $this->actingAs($this->normalUser, 'api')
        ->postJson('api/categories', $data)
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Your subscription is inactive. Please renew to perform write operations.',
        ]);

    $this->assertDatabaseMissing('categories', [
        'name' => 'Expired Sub Cat',
    ]);
});

test('user with inactive or expired subscription can still perform read operations on inventory', function () {
    // Create a category under the user
    $category = Category::factory()->create([
        'name' => 'Existing Cat',
        'user_id' => $this->normalUser->id,
    ]);

    // expired subscription
    Subscription::factory()->expired()->create([
        'user_id' => $this->normalUser->id,
    ]);

    $this->actingAs($this->normalUser, 'api')
        ->getJson("api/categories/{$category->uuid}")
        ->assertStatus(200)
        ->assertJsonFragment([
            'name' => 'Existing Cat',
        ]);
});

test('admin user without active subscription is exempt from subscription check', function () {
    // Admin has no active subscriptions
    $data = ['name' => 'Admin Cat', 'user_id' => $this->adminUser->id];

    $this->actingAs($this->adminUser, 'api')
        ->postJson('api/categories', $data)
        ->assertStatus(201);

    $this->assertDatabaseHas('categories', [
        'name' => 'Admin Cat',
    ]);
});
