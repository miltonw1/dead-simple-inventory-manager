<?php

namespace Tests\Feature\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class BulkOperationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
        Passport::actingAs($this->user);
    }

    public function test_by_brand_updates_prices()
    {
        $brand = Brand::factory()->create(['user_id' => $this->user->id]);
        $products = Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'brand_id' => $brand->id,
            'price' => 100,
        ]);

        $response = $this->postJson(route('bulk-operations.brand', $brand), [
            'type' => 'price_percentage',
            'value' => 20,
        ]);

        $response->assertOk();

        $products->each(function ($product) {
            $product->refresh();
            $this->assertEqualsWithDelta(120.0, $product->price, 0.01);
        });
    }

    public function test_by_category_updates_prices()
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        $products = Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'price' => 50,
        ]);
        $category->products()->attach($products);

        $response = $this->postJson(route('bulk-operations.category', $category), [
            'type' => 'price_fixed',
            'value' => 10,
        ]);

        $response->assertOk();

        $products->each(function ($product) {
            $product->refresh();
            $this->assertEqualsWithDelta(60.0, $product->price, 0.01);
        });
    }

    public function test_by_supplier_updates_prices()
    {
        $supplier = Supplier::factory()->create(['user_id' => $this->user->id]);
        $products = Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'supplier_id' => $supplier->id,
            'price' => 200,
        ]);

        $response = $this->postJson(route('bulk-operations.supplier', $supplier), [
            'type' => 'price_percentage',
            'value' => -10,
        ]);

        $response->assertOk();

        $products->each(function ($product) {
            $product->refresh();
            $this->assertEqualsWithDelta(180.0, $product->price, 0.01);
        });
    }

    public function test_update_stock_bulk()
    {
        $products = Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'stock' => 10,
        ]);

        $changes = [
            ['id' => $products[0]->id, 'value' => 15],
            ['id' => $products[1]->id, 'value' => 5],
        ];

        $response = $this->postJson('/api/bulk-operations/stock', [
            'type' => 'adjustment',
            'changes' => $changes,
        ]);

        $response->assertOk();

        $products[0]->refresh();
        $products[1]->refresh();

        expect($products[0]->stock)->toBe(15);
        expect($products[1]->stock)->toBe(5);
    }

    public function test_by_brand_denies_access_to_other_users_brand()
    {
        $otherUser = User::factory()->create();
        $brand = Brand::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson(route('bulk-operations.brand', $brand), [
            'type' => 'price_percentage',
            'value' => 10,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_access_other_users_brand()
    {
        Passport::actingAs($this->admin);
        $otherUser = User::factory()->create();
        $brand = Brand::factory()->create(['user_id' => $otherUser->id]);
        $product = Product::factory()->create([
            'user_id' => $otherUser->id,
            'brand_id' => $brand->id,
            'price' => 100,
        ]);

        $response = $this->postJson(route('bulk-operations.brand', $brand), [
            'type' => 'price_percentage',
            'value' => 10,
        ]);

        $response->assertOk();
        $product->refresh();
        $this->assertEqualsWithDelta(110.0, $product->price, 0.01);
    }
}
