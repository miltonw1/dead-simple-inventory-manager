<?php

namespace App\Http\Controllers;

use App\Http\Requests\Brand\StoreRequest;
use App\Http\Requests\Brand\UpdateRequest;
use App\Models\Brand;
use Illuminate\Http\Request;


class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Brand::class);

        $user = $request->user('api');

        return Brand::forUser($user)->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $user = $request->user('api');

        $brand = new Brand($request->validated());

        $user->brands()->save($brand);

        return $brand;
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        $this->authorize('view', $brand);

        $brand->load('products');

        return $brand;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Brand $brand)
    {
        $brand->fill($request->validated())->save();

        return $brand;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        $this->authorize('delete', $brand);

        $brand->delete();

        return $brand;

    }
}
