<?php

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('supplier belongs to user', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create(['user_id' => $user->id]);

    expect($supplier->user)->toBeInstanceOf(User::class);
    expect($supplier->user->id)->toBe($user->id);
});

test('supplier has many products', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create(['user_id' => $user->id]);
    $products = Product::factory()->count(3)->create([
        'user_id' => $user->id,
        'supplier_id' => $supplier->id,
    ]);

    expect($supplier->products)->toHaveCount(3);
    expect($supplier->products->first())->toBeInstanceOf(Product::class);
});
