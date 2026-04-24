<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || ! $user->phone_verified_at) {
            return response()->json([
                'status' => 0,
                'data' => [],
                'message' => 'OTP verification is required.',
            ], 403);
        }

        return $next($request);
    }
}
