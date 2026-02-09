<?php

namespace Database\Factories;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InventoryMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['purchase', 'sale', 'adjustment', 'return']),
            'quantity' => $this->faker->numberBetween(-100, 100),
            'previous_stock' => $this->faker->numberBetween(0, 1000),
            'new_stock' => $this->faker->numberBetween(0, 1000),
            'notes' => $this->faker->sentence,
        ];
    }
}
