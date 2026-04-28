<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Hash;
use Propaganistas\LaravelPhone\PhoneNumber;

class ProfileService
{
    public function updateProfile($request)
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'data' => null,
                'message' => 'User not authenticated',
                'code' => 401,
            ];
        }
        $phone = new PhoneNumber($request->phone);

        $normalized = $phone->formatE164();

        $user->update([
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
            'phone' => $normalized ?? $user->phone,
        ]);

        return [
            'data' => $user->fresh(),
            'message' => 'Profile updated successfully',
            'code' => 200,
        ];
    }

    public function updatePassword($request)
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'data' => null,
                'message' => 'User not authenticated',
                'code' => 401,
            ];
        }

        // 1. Check old password
        if (! Hash::check($request->old_password, $user->password)) {
            return [
                'data' => null,
                'message' => 'Old password is incorrect',
                'code' => 400,
            ];
        }

        // 2. Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // 3. Optional: logout all sessions (recommended with JWT)
        auth()->logout();

        return [
            'data' => null,
            'message' => 'Password updated successfully',
            'code' => 200,
        ];
    }
}
