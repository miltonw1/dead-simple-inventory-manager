<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use RuntimeException;

class SubscriptionCheckoutController extends Controller
{
    public function store(Request $request, MercadoPagoService $mercadoPago)
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:monthly,quarterly,yearly'],
        ]);

        $user = $request->user();

        try {
            $plan = $mercadoPago->plan($validated['plan']);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'provider' => 'mercado_pago',
            'plan' => $validated['plan'],
            'amount' => $plan['amount'],
            'currency' => $plan['currency'],
            'starts_at' => now(),
            'ends_at' => now(),
        ]);

        $subscription->external_reference = 'subscription:'.$subscription->uuid;
        $subscription->save();
        $subscription->load('user');

        try {
            $preference = $mercadoPago->createPaymentPreference($subscription);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        $subscription->fill([
            'provider_subscription_id' => $preference['id'] ?? null,
            'last_payment_status' => 'pending',
        ])->save();

        return response()->json([
            'checkout_url' => $preference['init_point'] ?? $preference['sandbox_init_point'] ?? null,
            'subscription' => $subscription,
        ], 201);

    }
}
