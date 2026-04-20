<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadUserProfile
{
    /**
     * Pre-loads the correct profile relationship for the authenticated user
     * based on their role — once per request, before any controller runs.
     *
     * This prevents repeated DB hits inside every scopeForUser() call
     * since loadMissing() only queries if the relation isn't already loaded.
     *
     * Technique 1 (roles)       → uses hasRole()
     * Technique 2 (permissions) → uses can() — same middleware works for both
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            match (true) {
                $user->hasRole('company-manager') => $user->loadMissing('ownedCompany'),
                $user->hasRole('branch-manager') => $user->loadMissing('ownedBranch'),
                $user->hasRole('employee') => $user->loadMissing('employeeProfile'),
                $user->hasRole('driver') => $user->loadMissing('driverProfile'),
                $user->hasRole('customer') => $user->loadMissing('customerProfile'),
                default => null, // super-admin needs no profile
            };
        }

        return $next($request);
    }
}
