<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Adjust the stock of a product and record the movement.
     *
     * @param  User  $user  The user making the adjustment.
     * @param  Product  $product  The product to adjust.
     * @param  int  $quantity  The quantity to add (positive) or subtract (negative).
     * @param  string  $type  The type of movement (purchase, sale, adjustment, return).
     * @param  string|null  $notes  Optional notes for the movement.
     */
    public function adjustStock(User $user, Product $product, int $quantity, string $type = 'adjustment', ?string $notes = null): Product
    {
        return DB::transaction(function () use ($user, $product, $quantity, $type, $notes) {
            $previousStock = $product->stock;
            $newStock = $previousStock + $quantity;

            // Record the movement
            InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'notes' => $notes,
            ]);

            // Update product stock
            $product->update(['stock' => $newStock]);

            return $product;
        });
    }
}
