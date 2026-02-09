<?php

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Models\User;
use App\Services\StockOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOperationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected StockOperation $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = app(StockOperation::class);
    }

    public function test_update_stock_increases_stock()
    {
        $product1 = Product::factory()->create(['user_id' => $this->user->id, 'stock' => 10]);
        $product2 = Product::factory()->create(['user_id' => $this->user->id, 'stock' => 5]);

        $changes = [
            ['id' => $product1->id, 'value' => 15],
            ['id' => $product2->id, 'value' => 10],
        ];

        $this->service->updateStock($this->user, $changes, 'adjustment');

        $product1->refresh();
        $product2->refresh();

        expect($product1->stock)->toBe(15);
        expect($product2->stock)->toBe(10);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product1->id,
            'quantity' => 5,
            'previous_stock' => 10,
            'new_stock' => 15,
            'type' => 'adjustment',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product2->id,
            'quantity' => 5,
            'previous_stock' => 5,
            'new_stock' => 10,
            'type' => 'adjustment',
        ]);
    }

    public function test_update_stock_decreases_stock()
    {
        $product = Product::factory()->create(['user_id' => $this->user->id, 'stock' => 20]);

        $changes = [
            ['id' => $product->id, 'value' => 8],
        ];

        $this->service->updateStock($this->user, $changes, 'sale');

        $product->refresh();
        expect($product->stock)->toBe(8);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'quantity' => -12,
            'previous_stock' => 20,
            'new_stock' => 8,
            'type' => 'sale',
        ]);
    }

    public function test_update_stock_only_affects_user_products()
    {
        $otherUser = User::factory()->create();
        $userProduct = Product::factory()->create(['user_id' => $this->user->id, 'stock' => 10]);
        $otherProduct = Product::factory()->create(['user_id' => $otherUser->id, 'stock' => 10]);

        $changes = [
            ['id' => $userProduct->id, 'value' => 15],
            ['id' => $otherProduct->id, 'value' => 15], // Should not affect
        ];

        $this->service->updateStock($this->user, $changes, 'adjustment');

        $userProduct->refresh();
        $otherProduct->refresh();

        expect($userProduct->stock)->toBe(15);
        expect($otherProduct->stock)->toBe(10); // Unchanged
    }

    public function test_update_stock_handles_empty_changes()
    {
        $this->service->updateStock($this->user, [], 'adjustment');

        // Should not throw error
        expect(true)->toBeTrue();
    }
}
