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
    Route::post('/subscription/verify-pending', [SubscriptionController::class, 'verifyPending']);
    Route::get('/plans', [SubscriptionController::class, 'plans']);
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

Route::post('/webhooks/mercado-pago', function (\Illuminate\Http\Request $request) {
    Log::info('MP WEBHOOK RECEIVED', $request->all());

    $data = $request->all();

    $topic = $request->query('topic') ?? $data['topic'] ?? $data['type'] ?? '';
    $paymentId = $request->query('id') ?? $data['data']['id'] ?? $data['id'] ?? '';

    if ($topic !== 'payment' || ! $paymentId) {
        Log::warning('MP WEBHOOK: Unhandled topic or missing payment ID', [
            'topic' => $topic,
            'paymentId' => $paymentId,
        ]);

        return response()->json(['ok' => true]);
    }

    $mercadoPago = app(\App\Services\MercadoPagoService::class);

    try {
        $payment = $mercadoPago->getPayment($paymentId);
    } catch (\RuntimeException $e) {
        Log::error('MP WEBHOOK: Failed to fetch payment', [
            'paymentId' => $paymentId,
            'error' => $e->getMessage(),
        ]);

        return response()->json(['ok' => true]);
    }

    $externalReference = $payment['external_reference'] ?? null;
    $paymentStatus = $payment['status'] ?? null;

    if (! $externalReference) {
        Log::warning('MP WEBHOOK: Payment has no external_reference', ['paymentId' => $paymentId]);

        return response()->json(['ok' => true]);
    }

    $subscription = \App\Models\Subscription::where('external_reference', $externalReference)->first();

    if (! $subscription) {
        Log::warning('MP WEBHOOK: No subscription found', [
            'external_reference' => $externalReference,
        ]);

        return response()->json(['ok' => true]);
    }

    if ($paymentStatus !== 'approved') {
        $subscription->update(['last_payment_status' => $paymentStatus]);

        Log::info("MP WEBHOOK: Payment not approved, skipping", [
            'status' => $paymentStatus,
            'uuid' => $subscription->uuid,
        ]);

        return response()->json(['ok' => true]);
    }

    $plan = $mercadoPago->plan($subscription->plan);
    $planDays = $plan['days'];

    $now = now();

    $activeSub = $subscription->user->subscriptions()
        ->where('status', 'active')
        ->where('ends_at', '>=', $now)
        ->latest('ends_at')
        ->first();

    if ($activeSub && $activeSub->ends_at && $activeSub->ends_at->isFuture()) {
        $newEndsAt = $activeSub->ends_at->copy()->addDays($planDays);
        $activeSub->fill(['ends_at' => $newEndsAt])->save();
    } else {
        $newEndsAt = $now->copy()->addDays($planDays);
    }

    $subscription->fill([
        'status' => 'active',
        'last_payment_status' => 'approved',
        'provider_payment_id' => $paymentId,
        'starts_at' => $now,
        'ends_at' => $newEndsAt,
    ])->save();

    Log::info("Subscription {$subscription->uuid} activated via Webhook payment.", [
        'new_ends_at' => $newEndsAt->toDateTimeString(),
        'days_added' => $planDays,
        'accumulated' => $activeSub && $activeSub->ends_at && $activeSub->ends_at->isFuture(),
    ]);

    return response()->json(['ok' => true]);
});


