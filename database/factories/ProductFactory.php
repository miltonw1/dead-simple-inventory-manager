<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => Str::uuid(),
            'name' => $this->faker->name(),
            'code' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 1, 1000),
            'description' => $this->faker->sentence,
            'stock' => $this->faker->numberBetween(5, 50),
            'min_stock_warning' => $this->faker->numberBetween(0, 10),
            'user_id' => User::factory(),
        ];
    }
}
