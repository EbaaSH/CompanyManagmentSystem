<?php

namespace App\Http\Middleware;

use App\Models\OTP;
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
        $otp = OTP::where('phone', $user->phone)->latest()->first();
        if (!$user || !$user->phone_verified_at || $otp->used == false) {
            return response()->json([
                'status' => 0,
                'data' => [],
                'message' => 'OTP verification is required.',
            ], 403);
        }

        return $next($request);
    }
}
