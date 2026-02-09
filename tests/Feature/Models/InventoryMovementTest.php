<?php

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inventory movement has fillable attributes', function () {
    $fillable = ['product_id', 'user_id', 'type', 'quantity', 'previous_stock', 'new_stock', 'notes'];

    expect(InventoryMovement::make()->getFillable())->toBe($fillable);
});

test('inventory movement belongs to product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $user->id]);

    $movement = InventoryMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    expect($movement->product)->toBeInstanceOf(Product::class);
    expect($movement->product->id)->toBe($product->id);
});

test('inventory movement belongs to user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $user->id]);

    $movement = InventoryMovement::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    expect($movement->user)->toBeInstanceOf(User::class);
    expect($movement->user->id)->toBe($user->id);
});
