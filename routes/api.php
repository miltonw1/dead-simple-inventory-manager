<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\BulkOperationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionCheckoutController;
use App\Http\Controllers\StorageLocationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

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

    Route::get('/user/subscription', [SubscriptionController::class, 'index']);
    Route::post('/subscription/checkout', [SubscriptionCheckoutController::class, 'store']);
    Route::post('/admin/subscriptions', [SubscriptionController::class, 'store']);
    Route::patch('/admin/subscriptions/{subscription}', [SubscriptionController::class, 'update']);

    Route::middleware('subscription.active')->group(function () {
        Route::apiResources([
            'brands' => BrandController::class,
            'categories' => CategoryController::class,
            'products' => ProductController::class,
            'suppliers' => SupplierController::class,
            'storage-locations' => StorageLocationController::class,
        ]);

        Route::prefix('bulk-operations')->name('bulk-operations.')->group(function () {
            Route::post('/stock', [BulkOperationController::class, 'updateStock']);
            Route::post('/brands/{brand}', [BulkOperationController::class, 'byBrand'])->name('brand');
            Route::post('/categories/{category}', [BulkOperationController::class, 'byCategory'])->name('category');
            Route::post('/suppliers/{supplier}', [BulkOperationController::class, 'bySupplier'])->name('supplier');
        });

        Route::put('/products/{product}/stock', [ProductController::class, 'updateStock']);
        Route::post('/products/{product}/image', [ProductController::class, 'updateImage']);
    });

    Route::apiResources([
        'users' => UserController::class,
    ]);
    Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);

    Route::get('/data', [DataController::class, 'index']);
});

Route::post('/webhooks/mercado-pago', function (Request $request) {
    Log::info('MP WEBHOOK RECEIVED', $request->all());

    $data = $request->all();
    $action = $data['type'] ?? $data['action'] ?? '';
    $isRenewal = $action === 'subscription_authorized_payment';

    // Buscar la suscripción por diferentes IDs que puede enviar Mercado Pago
    $candidateIds = array_filter([
        $data['data']['preapproval_id'] ?? null,
        $data['data']['id'] ?? null,
        $data['id'] ?? null,
    ]);

    $subscription = null;
    foreach ($candidateIds as $id) {
        $subscription = \App\Models\Subscription::where('provider_subscription_id', $id)->first();
        if ($subscription) {
            break;
        }
    }

    if (! $subscription) {
        Log::warning('MP WEBHOOK: No subscription found', ['candidateIds' => $candidateIds]);
        return response()->json(['ok' => true]);
    }

    $status = $data['data']['status'] ?? $data['status'] ?? null;
    $paymentId = $data['data']['id'] ?? null;

    if (! in_array($status, ['authorized', 'approved', 'active'])) {
        return response()->json(['ok' => true]);
    }

    if ($isRenewal) {
        // Pago recurrente: extender ends_at desde su valor actual
        $subscription->update([
            'last_payment_status' => 'approved',
            'provider_payment_id' => $paymentId,
            'ends_at' => match ($subscription->plan) {
                'monthly' => $subscription->ends_at->copy()->addMonth(),
                'quarterly' => $subscription->ends_at->copy()->addMonths(3),
                'yearly' => $subscription->ends_at->copy()->addYear(),
                default => $subscription->ends_at->copy()->addMonth(),
            },
        ]);

        Log::info("Subscription {$subscription->uuid} extended via Webhook renewal.");
    } elseif ($subscription->status !== 'active') {
        // Primera activación: establecer fechas desde ahora
        $subscription->update([
            'status' => 'active',
            'last_payment_status' => 'approved',
            'provider_payment_id' => $paymentId,
            'starts_at' => now(),
            'ends_at' => match ($subscription->plan) {
                'monthly' => now()->addMonth(),
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            },
        ]);

        Log::info("Subscription {$subscription->uuid} activated via Webhook.");
    }

    return response()->json(['ok' => true]);
});


