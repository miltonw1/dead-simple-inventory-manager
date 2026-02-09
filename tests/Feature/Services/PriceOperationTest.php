<?php

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Models\User;
use App\Services\PriceOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceOperationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected PriceOperation $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = app(PriceOperation::class);
    }

    public function test_percentual_price_transformation_increases_prices()
    {
        $products = Product::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'price' => 100,
        ]);

        $this->service->percentualPriceTransformation($this->user, $products->pluck('id')->toArray(), 10);

        $products->each(function ($product) {
            $product->refresh();
            $this->assertEqualsWithDelta(110.0, $product->price, 0.01);
            expect($product->last_price_update)->not->toBeNull();
        });
    }

    public function test_percentual_price_transformation_decreases_prices()
    {
        $products = Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'price' => 200,
        ]);

        $this->service->percentualPriceTransformation($this->user, $products->pluck('id')->toArray(), -20);

        $products->each(function ($product) {
            $product->refresh();
            $this->assertEqualsWithDelta(160.0, $product->price, 0.01);
        });
    }

    public function test_fixed_price_transformation_increases_prices()
    {
        $products = Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'price' => 50,
        ]);

        $this->service->fixedPriceTransformation($this->user, $products->pluck('id')->toArray(), 25);

        $products->each(function ($product) {
            $product->refresh();
            $this->assertEqualsWithDelta(75.0, $product->price, 0.01);
        });
    }

    public function test_fixed_price_transformation_decreases_prices()
    {
        $products = Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'price' => 100,
        ]);

        $this->service->fixedPriceTransformation($this->user, $products->pluck('id')->toArray(), -30);

        $products->each(function ($product) {
            $product->refresh();
            $this->assertEqualsWithDelta(70.0, $product->price, 0.01);
        });
    }

    public function test_price_transformation_only_affects_user_products()
    {
        $otherUser = User::factory()->create();
        $userProducts = Product::factory()->count(2)->create(['user_id' => $this->user->id, 'price' => 100]);
        $otherProducts = Product::factory()->count(2)->create(['user_id' => $otherUser->id, 'price' => 100]);

        $this->service->percentualPriceTransformation($this->user, $userProducts->pluck('id')->toArray(), 10);

        $userProducts->each(fn ($p) => $this->assertEqualsWithDelta(110.0, $p->fresh()->price, 0.01));
        $otherProducts->each(fn ($p) => expect($p->fresh()->price)->toEqual(100.0));
    }
}
