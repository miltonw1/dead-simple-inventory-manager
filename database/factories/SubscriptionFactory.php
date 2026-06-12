<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => Str::uuid(),
            'user_id' => User::factory(),
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ];
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'expired',
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->subMonth(),
            ];
        });
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'starts_at' => now()->subMonth(),
                'ends_at' => now()->addMonth(),
            ];
        });
    }
}
