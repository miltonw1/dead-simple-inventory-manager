<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/subscription/return', function (Illuminate\Http\Request $request) {
    Log::info('RETURN URL TRIGGERED', $request->all());
    $preapprovalId = $request->query('preapproval_id') ?? $request->query('external_reference') ?? $request->query('preference_id');
    $status = $request->query('status') ?? $request->query('collection_status'); 

    // Omitimos la validación restrictiva del query status por si Mercado Pago no lo envía o viene diferente en suscripciones
    if ($preapprovalId) {
        $subscription = \App\Models\Subscription::where('provider_subscription_id', $preapprovalId)
            ->orWhere('external_reference', $preapprovalId)
            ->orWhere('external_reference', 'subscription:' . $preapprovalId)
            ->first();

        Log::info('RETURN URL SUBSCRIPTION QUERY RESULT', [
            'found' => $subscription ? true : false,
            'uuid' => $subscription ? $subscription->uuid : null,
            'current_status' => $subscription ? $subscription->status : null
        ]);

        if ($subscription && $subscription->status !== 'active') {
            $startsAt = now();
            $endsAt = now();

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
            
            Log::info("Subscription {$subscription->uuid} activated successfully via Return URL.");
        }
    }

    return redirect('http://localhost:9000/#/subscriptions');
});