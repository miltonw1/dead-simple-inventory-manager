<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_update_is_logged()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        $user->update(['name' => 'New Name']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => "User updated by {$user->name}({$user->id})",
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $activity = Activity::all()->last();

        $this->assertEquals('New Name', $activity->properties['attributes']['name']);
        $this->assertEquals('Original Name', $activity->properties['old']['name']);
    }

    public function test_user_password_update_is_not_logged()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Change name AND password to ensure log is created but password is missing
        $user->update([
            'name' => 'Updated Name',
            'password' => 'another-password',
        ]);

        $activity = Activity::all()->last();
        $this->assertArrayHasKey('name', $activity->properties['attributes']);
        $this->assertArrayNotHasKey('password', $activity->properties['attributes']);
    }

    public function test_product_stock_update_is_not_logged()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'user_id' => $user->id,
            'stock' => 10,
            'price' => 100,
        ]);

        // Update stock only -> should NOT create log because of dontSubmitEmptyLogs + logExcept('stock')
        $product->update(['stock' => 20]);

        $lastActivity = Activity::all()->last();
        // The last activity should be the creation of the product
        $this->assertEquals("Product created by {$user->name}({$user->id})", $lastActivity->description);

        // Now update price -> should be logged
        $product->update(['price' => 200]);

        $lastActivity = Activity::all()->last();
        $this->assertEquals("Product updated by {$user->name}({$user->id})", $lastActivity->description);
        $this->assertEquals(200, $lastActivity->properties['attributes']['price']);
        $this->assertArrayNotHasKey('stock', $lastActivity->properties['attributes']);
    }

    public function test_brand_creation_is_logged()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $brand = Brand::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Brand::class,
            'subject_id' => $brand->id,
            'description' => "Brand created by {$user->name}({$user->id})",
        ]);
    }

    public function test_category_creation_is_logged()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Category::class,
            'subject_id' => $category->id,
            'description' => "Category created by {$user->name}({$user->id})",
        ]);
    }

    public function test_supplier_creation_is_logged()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Supplier::class,
            'subject_id' => $supplier->id,
            'description' => "Supplier created by {$user->name}({$user->id})",
        ]);
    }

    public function test_storage_location_creation_is_logged()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $location = StorageLocation::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => StorageLocation::class,
            'subject_id' => $location->id,
            'description' => "StorageLocation created by {$user->name}({$user->id})",
        ]);
    }
}
