<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;

test('brand has fillable attributes', function () {
    $brand = new Brand([
        'name' => 'Test Brand',
        'user_id' => 1,
    ]);

    expect($brand->name)->toBe('Test Brand')
        ->and($brand->user_id)->toBe(1);
});

test('brand belongs to user', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->create(['user_id' => $user->id]);

    expect($brand->user)->toBeInstanceOf(User::class)
        ->and($brand->user->id)->toBe($user->id);
});

test('brand has products', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->create(['user_id' => $user->id]);
    $products = Product::factory()->count(3)->create([
        'user_id' => $user->id,
        'brand_id' => $brand->id,
    ]);

    expect($brand->products)->toHaveCount(3)
        ->and($brand->products->first())->toBeInstanceOf(Product::class);
});
