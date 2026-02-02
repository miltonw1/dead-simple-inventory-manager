<?php

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user and authenticate as we need user_id for movements
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    /** @test */
    public function scope_low_stock_filters_correctly()
    {
        // 1. Create products with different stock levels
        Product::factory()->create(['stock' => 10, 'min_stock_warning' => 20]); // Low stock
        Product::factory()->create(['stock' => 5, 'min_stock_warning' => 5]);   // Low stock (equal)
        Product::factory()->create(['stock' => 50, 'min_stock_warning' => 10]); // OK stock

        // 2. Use the scope
        $lowStockProducts = Product::lowStock()->get();

        // 3. Assertions
        $this->assertCount(2, $lowStockProducts);
        $this->assertTrue($lowStockProducts->contains(fn ($p) => $p->stock === 10));
        $this->assertTrue($lowStockProducts->contains(fn ($p) => $p->stock === 5));
        $this->assertFalse($lowStockProducts->contains(fn ($p) => $p->stock === 50));
    }

    /** @test */
    public function product_observer_updates_timestamps()
    {
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);

        // Initial state: timestamps should be null or close to creation
        $this->assertNull($product->last_price_update);
        $this->assertNull($product->last_stock_update);

        // 1. Update Price
        $product->update(['price' => 150]);
        $this->assertNotNull($product->fresh()->last_price_update);

        // 2. Update Stock (Direct update to test observer, though we should use service usually)
        // We use fresh() to ensure we get the db state updated by observer
        $product = $product->fresh();
        $oldStockTimestamp = $product->last_stock_update;

        $product->update(['stock' => 20]);
        $this->assertNotNull($product->fresh()->last_stock_update);
        $this->assertNotEquals($oldStockTimestamp, $product->fresh()->last_stock_update);
    }

    /** @test */
    public function inventory_service_creates_movements_and_updates_stock()
    {
        $service = app(InventoryService::class);
        $product = Product::factory()->create(['stock' => 10, 'user_id' => $this->user->id]);

        // 1. Perform adjustment
        $service->adjustStock($this->user, $product, 5, 'purchase', 'Restocking');
        // 2. Verify Product Stock
        $this->assertEquals(15, $product->fresh()->stock);

        // 3. Verify Movement Record
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 5,
            'previous_stock' => 10,
            'new_stock' => 15,
            'notes' => 'Restocking',
        ]);
    }

    /** @test */
    public function product_controller_store_creates_initial_movement()
    {
        $payload = Product::factory()->make(['stock' => 50])->toArray();
        // make() doesn't give us the relations needed for validation sometimes, so we strip them or handle them if needed.
        // Factory make() provides raw attributes.

        $response = $this->postJson(route('products.store'), $payload);

        $response->assertCreated();
        $product = Product::latest()->first();

        // Assert Stock
        $this->assertEquals(50, $product->stock);

        // Assert Movement
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 50,
            'new_stock' => 50,
            'notes' => 'Initial stock',
        ]);
    }

    /** @test */
    public function product_controller_update_creates_adjustment_movement()
    {
        $product = Product::factory()->create(['stock' => 10, 'user_id' => $this->user->id]);

        $payload = $product->toArray();
        $payload['stock'] = 15; // Change stock
        $payload['name'] = 'Updated Name';

        $response = $this->putJson(route('products.update', $product), $payload);

        $response->assertOk();

        // Assert Movement
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment', // Default type in controller
            'quantity' => 5, // 15 - 10
            'previous_stock' => 10,
            'new_stock' => 15,
        ]);
    }
}
