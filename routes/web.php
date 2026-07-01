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

Route::get('/subscription/return', function (\Illuminate\Http\Request $request) {
    Log::info('RETURN URL TRIGGERED', $request->all());

    $externalRef = $request->query('external_reference');
    $preferenceId = $request->query('preference_id');
    $status = $request->query('status');
    $paymentId = $request->query('payment_id');

    if ($externalRef || $preferenceId) {
        $subscription = \App\Models\Subscription::where('external_reference', $externalRef)
            ->orWhere('provider_subscription_id', $preferenceId)
            ->first();

        Log::info('RETURN URL SUBSCRIPTION QUERY RESULT', [
            'found' => $subscription ? true : false,
            'uuid' => $subscription ? $subscription->uuid : null,
            'current_status' => $subscription ? $subscription->status : null,
        ]);

        if ($subscription && $status === 'approved' && $subscription->status !== 'active') {
            $planDays = match ($subscription->plan) {
                'monthly' => 30,
                'quarterly' => 90,
                'yearly' => 365,
                default => 30,
            };

            $now = now();

            $activeSub = $subscription->user->subscriptions()
                ->where('status', 'active')
                ->where('ends_at', '>=', $now)
                ->latest('ends_at')
                ->first();

            $newEndsAt = $activeSub && $activeSub->ends_at && $activeSub->ends_at->isFuture()
                ? $activeSub->ends_at->copy()->addDays($planDays)
                : $now->copy()->addDays($planDays);

            if ($activeSub && $activeSub->ends_at && $activeSub->ends_at->isFuture()) {
                $activeSub->fill(['ends_at' => $newEndsAt])->save();
            }

            $subscription->fill([
                'status' => 'active',
                'last_payment_status' => 'approved',
                'provider_payment_id' => $paymentId,
                'starts_at' => $now,
                'ends_at' => $newEndsAt,
            ])->save();

            Log::info("Subscription {$subscription->uuid} activated via Return URL.", [
                'new_ends_at' => $newEndsAt->toDateTimeString(),
                'days_added' => $planDays,
            ]);
        }
    }

    return redirect('http://localhost:9000/#/subscriptions?payment=approved');
});