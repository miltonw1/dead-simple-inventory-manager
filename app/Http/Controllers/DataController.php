<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;

class DataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return array<string, mixed>
     */
    public function index(): array
    {
        $categories = Category::count();
        $products = Product::count();
        $suppliers = Supplier::count();

        $with_stock = Product::where('stock', '>', 0)->get();

        $different_products = $with_stock->count();
        $products_amount = $with_stock->sum('stock');
        $money_in_products = $with_stock->sum('price');

        return [
            'categories' => $categories,
            'products' => $products,
            'suppliers' => $suppliers,
            'different_products' => $different_products,
            'products_amount' => $products_amount,
            'money_in_products' => $money_in_products,
        ];
    }
}
