<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockOperation
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * Update stock for multiple products based on provided changes.
     *
     * @param  User  $user  The user performing the operation.
     * @param  array  $changes  An associative array where keys are product IDs and values are the new stock values.
     * @param  string|null  $type  The type of stock movement (default is 'adjustment').
     */
    public function updateStock(User $user, array $changes, ?string $type = 'adjustment'): int
    {
        // [{ id: 1, value: 10 }, { id: 2, value: 5 }]
        $productIds = array_map(fn ($change) => $change['id'], $changes);
        $productMap = array_combine($productIds, $changes);

        $products = Product::whereIn('id', $productIds)
            ->when(! $user->is_admin, fn ($q) => $q->where('user_id', $user->id))
            ->get();

        if ($products->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($user, $products, $productMap, $type) {
            foreach ($products as $product) {
                $newStock = $productMap[$product->id]['value'] ?? $product->stock;
                $previousStock = $product->stock;
                $quantity = $newStock - $previousStock;

                $this->inventory->leanAdjustStock(
                    $user,
                    $product,
                    $quantity,
                    $type,
                    'Inventory movement'
                );
            }

            return count($products);
        });
    }
}
