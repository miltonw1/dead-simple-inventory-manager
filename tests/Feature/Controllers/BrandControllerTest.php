<?php

use App\Models\Brand;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
});

test('brands index', function () {
    Brand::factory()->count(3)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user, 'api')
        ->getJson('api/brands')
        ->assertStatus(200)
        ->assertJsonStructure([
            '*' => ['name', 'user_id'],
        ]);
});

test('brands store', function () {
    $this->assertDatabaseCount('brands', 0);

    $data = Brand::factory()->make(['user_id' => $this->user->id])->toArray();

    $this->actingAs($this->user, 'api')
        ->postJson('api/brands', $data)
        ->assertStatus(201)
        ->assertJsonStructure([
            'name',
            'user_id',
        ]);

    $this->assertDatabaseCount('brands', 1);
});

test('brands show', function () {
    $brand = Brand::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user, 'api')
        ->getJson("api/brands/{$brand->uuid}")
        ->assertStatus(200)
        ->assertJsonStructure([
            'name',
            'products',
        ]);
});

test('brands update', function () {
    $brand = Brand::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user, 'api')
        ->putJson("api/brands/{$brand->uuid}", ['name' => 'TEST NAME'])
        ->assertStatus(200)
        ->assertJsonStructure([
            'name',
        ]);

    $this->assertDatabaseHas('brands', ['name' => 'TEST NAME']);
});

test('brands delete', function () {
    $brand = Brand::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user, 'api')
        ->deleteJson("api/brands/{$brand->uuid}")
        ->assertStatus(200)
        ->assertJsonStructure([
            'name',
        ]);

    $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
});

test('admin can see all brands', function () {
    // Create admin user
    $admin = User::factory()->create(['role' => 'admin']);

    // Create brands for different users
    $brand1 = Brand::factory()->create(['user_id' => $this->user->id]);
    $brand2 = Brand::factory()->create(['user_id' => $admin->id]);
    $anotherUser = User::factory()->create();
    $brand3 = Brand::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->actingAs($admin, 'api')
        ->getJson('api/brands')
        ->assertStatus(200);

    $brands = $response->json();

    // Admin should see all 3 brands
    expect(count($brands))->toBe(3);
});

test('non-admin user can only see their own brands', function () {
    // Create a non-admin user
    $regularUser = User::factory()->create(['role' => 'user']);

    // Create brands for different users
    $myBrand = Brand::factory()->create(['user_id' => $regularUser->id]);

    $anotherUser = User::factory()->create();
    $otherBrand = Brand::factory()->create(['user_id' => $anotherUser->id]);

    $response = $this->actingAs($regularUser, 'api')
        ->getJson('api/brands')
        ->assertStatus(200);

    $brands = $response->json();

    // Non-admin should only see their own brand
    expect(count($brands))->toBe(1);
    expect($brands[0]['uuid'])->toBe($myBrand->uuid->toString());
});
