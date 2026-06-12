<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Retrieve the current user's active/latest subscription status.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $latestSubscription = $user->subscriptions()
            ->latest()
            ->first();

        return response()->json([
            'has_active_subscription' => $user->hasActiveSubscription(),
            'subscription' => $latestSubscription,
        ]);
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
            'status' => 'required|string|in:active,expired,cancelled',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $subscription = Subscription::create($validated);

        return response()->json($subscription, 201);
    }
}
