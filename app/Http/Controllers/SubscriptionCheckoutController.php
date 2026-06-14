<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use RuntimeException;

class SubscriptionCheckoutController extends Controller
{
    /**
     * Create a Mercado Pago preapproval checkout for the authenticated user.
     */
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
            $preapproval = $mercadoPago->createPreapproval($subscription);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        $subscription->fill([
            'provider_subscription_id' => $preapproval['id'] ?? null,
            'last_payment_status' => $preapproval['status'] ?? null,
        ])->save();

        return response()->json([
            'checkout_url' => $preapproval['init_point'] ?? $preapproval['sandbox_init_point'] ?? null,
            'subscription' => $subscription,
        ], 201);
    }
}
