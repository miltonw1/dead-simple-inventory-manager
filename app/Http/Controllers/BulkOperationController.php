<?php

namespace App\Http\Controllers;

use App\Domain\Enums\PriceAdjustmentType;
use App\Http\Requests\Bulk\PriceRequest;
use App\Http\Requests\Bulk\StockRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PriceOperation;
use App\Services\StockOperation;

class BulkOperationController extends Controller
{
    public function __construct(
        protected PriceOperation $price,
        protected StockOperation $stock,
    ) {}

    public function byBrand(PriceRequest $request, Brand $brand)
    {
        $this->authorize('updatePrice', $brand);

        $productIds = $brand->products()->pluck('products.id')->toArray();

        return $this->transform('brand', $brand->name, $request, $productIds);
    }

    public function byCategory(PriceRequest $request, Category $category)
    {
        $this->authorize('updatePrice', $category);

        $productIds = $category->products()->pluck('products.id')->toArray();

        return $this->transform('category', $category->name, $request, $productIds);
    }

    public function bySupplier(PriceRequest $request, Supplier $supplier)
    {
        $this->authorize('updatePrice', $supplier);

        $productIds = $supplier->products()->pluck('products.id')->toArray();

        return $this->transform('supplier', $supplier->name, $request, $productIds);
    }

    public function updateStock(StockRequest $request)
    {
        $user = $request->user('api');

        $values = $request->validated();

        $affectedResources = $this->stock->updateStock(
            $user,
            $values['changes'],
            $values['type']
        );

        return response()->json([
            'affected_resources' => $affectedResources,
            'message' => "Stock updated for {$affectedResources} products",
        ]);
    }

    protected function makePriceOperation(User $user, array $productIds, PriceAdjustmentType $type, float|int $value): int
    {
        if ($type === PriceAdjustmentType::PERCENTAGE) {
            return $this->price->percentualPriceTransformation(
                $user,
                $productIds,
                $value
            );
        }
        if ($type === PriceAdjustmentType::FIXED) {
            return $this->price->fixedPriceTransformation(
                $user,
                $productIds,
                $value
            );
        }

        return 0;
    }

    protected function transform(
        string $entityType,
        string $entityName,
        $request,
        array $productIds
    ): \Illuminate\Http\JsonResponse {
        $user = $request->user('api');

        $values = $request->validated();

        $affectedResources = $this->makePriceOperation($user, $productIds, PriceAdjustmentType::from($values['type']), $values['value']);

        return response()->json([
            'affected_resources' => $affectedResources,
            'message' => "Price updated for {$affectedResources} products of {$entityType} {$entityName}",
        ]);
    }
}
