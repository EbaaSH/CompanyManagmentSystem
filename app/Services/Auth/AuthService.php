<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;

class AuthService
{
    /**
     * Get the token array structure.
     *
     * @param  string  $token
     * @return array
     */
    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ];
    }

    /**
     * Register a new user.
     *
     * @param  Request  $request
     * @return array
     */
    public function register($request)
    {
        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);

        $role = Role::findByName($request->role ?? 'user');
        $user->assignRole($role);

        $token = JWTAuth::fromUser($user);

        $data = ['token' => $token, 'user' => $user];

        return ['data' => $data, 'message' => 'Account created successfully', 'code' => 201];
    }

    /**
     * Login a user and return a JWT token.
     *
     * @return array
     */
    public function login($request)
    {
        $credentials = $request->only('email', 'password');

        // Attempt to create a token
        if (! $token = JWTAuth::attempt($credentials)) {
            return ['data' => null, 'message' => 'Unauthorized', 'code' => 401];
        }

        // Return success response with token
        return ['data' => $this->respondWithToken($token), 'message' => 'Login successful', 'code' => 200];
    }

    /**
     * Get the authenticated user's profile.
     *
     * @return array
     */
    public function me()
    {
        $user = auth()->user();

        if ($user) {
            return ['data' => $user, 'message' => 'User profile data', 'code' => 200];
        }

        return ['data' => null, 'message' => 'User not authenticated', 'code' => 401];
    }

    /**
     * Logout the current user.
     *
     * @return array
     */
    public function logout()
    {
        JWTAuth::logout();

        return ['data' => null, 'message' => 'Successfully logged out', 'code' => 200];
    }

    /**
     * Refresh the JWT token.
     *
     * @return array
     */
    public function refresh()
    {
        try {
            $refreshedToken = JWTAuth::refresh();

            return ['data' => $this->respondWithToken($refreshedToken), 'message' => 'Token refreshed successfully', 'code' => 200];
        } catch (\Exception $e) {
            return ['data' => null, 'message' => 'Unable to refresh token', 'code' => 401];
        }
    }
}
