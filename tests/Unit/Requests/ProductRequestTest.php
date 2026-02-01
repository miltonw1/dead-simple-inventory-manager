<?php

use App\Http\Requests\Product\StoreRequest;

test('rules keys', function () {
    $keys = array_keys((new StoreRequest)->rules());
    sort($keys);

    expect($keys)->toHaveCount(11)
        ->toEqual([
            'brand_id',
            'categories',
            'categories.*',
            'code',
            'description',
            'min_stock_warning',
            'name',
            'price',
            'stock',
            'storage_location_id',
            'supplier_id',
        ]);
});

test('rules values', function () {
    $rules = (new StoreRequest)->rules();

    expect($rules)->toBeArray()
        ->and($rules['brand_id'])->toEqual(['nullable', 'exists:brands,id'])
        ->and($rules['categories'])->toEqual(['array'])
        ->and($rules['categories.*'])->toEqual(['exists:categories,id'])
        ->and($rules['code'])->toEqual(['nullable'])
        ->and($rules['description'])->toEqual(['nullable', 'string'])
        ->and($rules['min_stock_warning'])->toEqual(['nullable', 'integer'])
        ->and($rules['name'])->toEqual(['required'])
        ->and($rules['price'])->toEqual(['nullable', 'numeric'])
        ->and($rules['stock'])->toEqual(['required', 'integer'])
        ->and($rules['storage_location_id'])->toEqual(['nullable', 'exists:storage_locations,id'])
        ->and($rules['supplier_id'])->toEqual(['nullable', 'exists:suppliers,id']);
});
