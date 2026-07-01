<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $latestSubscription = $user->subscriptions()
            ->latest()
            ->first();

        $daysRemaining = null;
        if ($latestSubscription && $latestSubscription->ends_at) {
            $now = now();
            $daysRemaining = $latestSubscription->ends_at->greaterThan($now)
                ? (int) $now->startOfDay()->diffInDays($latestSubscription->ends_at->startOfDay())
                : 0;
        }

        return response()->json([
            'has_active_subscription' => $user->hasActiveSubscription(),
            'subscription' => $latestSubscription,
            'days_remaining' => $daysRemaining,
        ]);
    }

    public function verifyPending(Request $request)
    {
        $user = $request->user();

        $latestSub = $user->subscriptions()->latest()->first();

        if (! $latestSub) {
            return response()->json(['verified' => false, 'message' => 'No subscription found.']);
        }

        if ($latestSub->status === 'active' || $user->hasActiveSubscription()) {
            $daysRemaining = $latestSub->ends_at && $latestSub->ends_at->greaterThan(now())
                ? (int) now()->startOfDay()->diffInDays($latestSub->ends_at->startOfDay())
                : 0;

            return response()->json([
                'verified' => true,
                'has_active_subscription' => true,
                'subscription' => $latestSub,
                'days_remaining' => $daysRemaining,
            ]);
        }

        return response()->json(['verified' => false, 'message' => 'Payment still pending.']);
    }

    public function plans(MercadoPagoService $mercadoPago)
    {
        $plans = config('services.mercado_pago.plans', []);

        return response()->json(array_map(function ($key, $plan) use ($mercadoPago) {
            return [
                'plan' => $key,
                'label' => $plan['label'],
                'amount' => (float) $plan['amount'],
                'currency' => $plan['currency'] ?? 'ARS',
                'days' => $plan['days'],
            ];
        }, array_keys($plans), $plans));
    }

    /**
     * Create a new subscription for a user (Admin only).
     */
    public function store(Request $request)
    {
        if (! $request->user()->is_admin) {
            abort(403, 'This action is unauthorized.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:pending,active,expired,cancelled',
            'provider' => 'sometimes|string|in:manual,mercado_pago',
            'provider_subscription_id' => 'nullable|string|max:255',
            'provider_payment_id' => 'nullable|string|max:255',
            'external_reference' => 'nullable|string|max:255',
            'plan' => 'nullable|string|in:monthly,quarterly,yearly',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'last_payment_status' => 'nullable|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'cancelled_at' => 'nullable|date',
        ]);

        $subscription = Subscription::create($validated);

        return response()->json($subscription, 201);
    }

    /**
     * Update an existing subscription (Admin only).
     */
    public function update(Request $request, Subscription $subscription)
    {
        if (! $request->user()->is_admin) {
            abort(403, 'This action is unauthorized.');
        }

        $endsAtRules = ['sometimes', 'date'];

        if ($request->filled('starts_at')) {
            $endsAtRules[] = 'after:starts_at';
        }

        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'status' => 'sometimes|string|in:pending,active,expired,cancelled',
            'provider' => 'sometimes|string|in:manual,mercado_pago',
            'provider_subscription_id' => 'nullable|string|max:255',
            'provider_payment_id' => 'nullable|string|max:255',
            'external_reference' => 'nullable|string|max:255',
            'plan' => 'nullable|string|in:monthly,quarterly,yearly',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'last_payment_status' => 'nullable|string|max:255',
            'starts_at' => 'sometimes|date',
            'ends_at' => $endsAtRules,
            'cancelled_at' => 'nullable|date',
        ]);

        $subscription->fill($validated)->save();

        return response()->json($subscription);
    }
}
