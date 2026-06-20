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
    $preapprovalId = $data['data']['id'] ?? $data['id'] ?? null;
    $type = $data['type'] ?? $data['action'] ?? null;

    // En Mercado Pago las notificaciones de suscripción pueden venir con type 'subscription_preapproval' o directo con la info de la suscripción
    if ($preapprovalId) {
        $subscription = \App\Models\Subscription::where('provider_subscription_id', $preapprovalId)
            ->orWhere('external_reference', $preapprovalId)
            ->first();

        if ($subscription) {
            // Obtenemos los detalles actuales desde Mercado Pago o mapeamos los del webhook
            // Para asegurar la validez, normalmente se haría un GET a /preapproval/{id}
            // Pero de forma directa, si la acción es de aprobación o si el webhook viene con status:
            $status = $data['data']['status'] ?? $data['status'] ?? null;
            
            // Si viene el status "authorized" o "approved", activamos la suscripción
            if (in_array($status, ['authorized', 'approved', 'active'])) {
                $startsAt = now();
                $endsAt = now();

                // Calcular fecha de finalización según el plan de la suscripción
                if ($subscription->plan === 'monthly') {
                    $endsAt = $startsAt->copy()->addMonth();
                } elseif ($subscription->plan === 'quarterly') {
                    $endsAt = $startsAt->copy()->addMonths(3);
                } elseif ($subscription->plan === 'yearly') {
                    $endsAt = $startsAt->copy()->addYear();
                }

                $subscription->update([
                    'status' => 'active',
                    'last_payment_status' => 'approved',
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                Log::info("Subscription {$subscription->uuid} activated successfully via Webhook.");
            }
        }
    }

    return response()->json(['ok' => true]);
});


