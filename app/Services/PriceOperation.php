<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PriceOperation
{
    /**
     * Apply a percentual price transformation to a list of products.
     */
    public function percentualPriceTransformation(User $user, array $productIds, float $percentage): int
    {
        return DB::transaction(function () use ($user, $productIds, $percentage) {
            $percentage = round($percentage / 100, 2);

            $newPriceExpression = $percentage > 0
                ? "price * (1 + $percentage)"
                : "price * (1 - ABS($percentage))";

            $affectedRows = Product::whereIn('id', $productIds)
                ->when(! $user->is_admin, fn ($q) => $q->where('user_id', $user->id))
                ->update([
                    'price' => DB::raw($newPriceExpression),
                    'last_price_update' => now(),
                ]);

            if ($affectedRows > 0) {
                activity()
                    ->performedOn(new Product)
                    ->causedBy($user)
                    ->withProperties([
                        'percentage' => $percentage * 100 .'%',
                        'products_count' => $affectedRows,
                        'product_ids' => $productIds,
                    ])
                    ->log("Massive product's prices updated by percentage done by {$user->name}({$user->id})");
            }

            return $affectedRows;
        });
    }

    /**
     * Apply a fixed amount price transformation to a list of products.
     */
    public function fixedPriceTransformation(User $user, array $productIds, float $amount): int
    {
        return DB::transaction(function () use ($user, $productIds, $amount) {
            $newPriceExpression = $amount > 0
                ? "price + ABS($amount)"
                : "price - ABS($amount)";

            $affectedRows = Product::whereIn('id', $productIds)
                ->when(! $user->is_admin, fn ($q) => $q->where('user_id', $user->id))
                ->update([
                    'price' => DB::raw($newPriceExpression),
                    'last_price_update' => now(),
                ]);

            if ($affectedRows > 0) {
                activity()
                    ->performedOn(new Product)
                    ->causedBy($user)
                    ->withProperties([
                        'fixed_amount' => $amount,
                        'products_count' => $affectedRows,
                        'product_ids' => $productIds,
                    ])
                    ->log("Massive product's prices updated by fixed amount done by {$user->name}({$user->id})");
            }

            return $affectedRows;
        });
    }
}
