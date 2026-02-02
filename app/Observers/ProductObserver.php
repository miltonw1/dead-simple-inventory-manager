<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "updating" event.
     */
    public function updating(Product $product): void
    {
        if ($product->isDirty('price')) {
            $product->last_price_update = now();
        }

        if ($product->isDirty('stock')) {
            $product->last_stock_update = now();
        }
    }
}
