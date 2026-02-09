<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\BulkOperationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StorageLocationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'profile']);
        Route::post('/', [ProfileController::class, 'updateProfile']);
        Route::post('/password', [ProfileController::class, 'updatePassword']);
    });

    Route::apiResources([
        'brands' => BrandController::class,
        'categories' => CategoryController::class,
        'products' => ProductController::class,
        'suppliers' => SupplierController::class,
        'storage-locations' => StorageLocationController::class,
        'users' => UserController::class,
    ]);

    Route::prefix('bulk-operations')->name('bulk-operations.')->group(function () {
        Route::post('/stock', [BulkOperationController::class, 'updateStock']);
        Route::post('/brands/{brand}', [BulkOperationController::class, 'byBrand'])->name('brand');
        Route::post('/categories/{category}', [BulkOperationController::class, 'byCategory'])->name('category');
        Route::post('/suppliers/{supplier}', [BulkOperationController::class, 'bySupplier'])->name('supplier');
    });

    Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);
    Route::put('/products/{product}/stock', [ProductController::class, 'updateStock']);
    Route::post('/products/{product}/image', [ProductController::class, 'updateImage']);
    Route::get('/data', [DataController::class, 'index']);
});
