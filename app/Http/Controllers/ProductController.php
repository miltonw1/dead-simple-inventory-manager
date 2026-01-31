<?php

namespace App\Http\Controllers;

use App\Domain\ImageManipulation;
use App\Http\Requests\Product\StoreRequest;
use App\Http\Requests\Product\UpdateImageRequest;
use App\Http\Requests\Product\UpdateRequest;
use App\Http\Requests\Product\UpdateStockRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(
        protected ImageManipulation $images
    ) {}

    /**
     * Display a listing of the resource.
     * Returns all products if user is admin, otherwise only user's products.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $user = $request->user('api');

        return Product::forUser($user)
            ->with('supplier', 'categories', 'storageLocation')
            ->orderBy('code')
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): Product
    {
        $user = $request->user('api');

        $product = new Product($request->validated());

        $user->products()->save($product);

        $product->categories()->attach($request->get('categories'));

        return $product;
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): Product
    {
        $this->authorize('view', $product);

        $product->load('supplier', 'categories', 'storageLocation');

        return $product;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Product $product): Product
    {
        $product->update($request->validated());

        $product->categories()->sync($request->get('categories'));

        return $product;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Product $product): Product
    {
        $this->authorize('delete', $product);

        $product->categories()->detach();

        $product->delete();

        return $product;
    }

    /**
     * Update the stock of the specified resource in storage.
     */
    public function updateStock(UpdateStockRequest $request, Product $product): Product
    {
        $this->authorize('updateStock', $product);

        $product->stock = $request->validated()['stock'];

        $product->save();

        return $product;
    }

    /**
     * Update the image of the specified resource in storage.
     */
    public function updateImage(UpdateImageRequest $request, Product $product): Product
    {
        $disk = Storage::disk('public');

        if ($product->image_path) {
            $disk->delete($product->image_path);
        }

        $file = $request->file('image');

        $filename = $this->images->getProductImageName();
        $disk->put($filename, $this->images->processProductImage($file));

        $product->image_path = $filename;
        $product->image_url = url(Storage::url($product->image_path));

        $product->save();

        return $product;
    }
}
