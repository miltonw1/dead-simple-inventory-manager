<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow safe methods (GET, HEAD, OPTIONS)
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && ! $user->hasActiveSubscription()) {
            return response()->json([
                'message' => 'Your subscription is inactive. Please renew to perform write operations.',
            ], 403);
        }

        return $next($request);
    }
}
