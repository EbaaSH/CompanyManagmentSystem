<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use libphonenumber\NumberParseException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Permission\Models\Role;

class AuthService
{
    private OTPService $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

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

        $this->otpService->sendOtp($user->phone);

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

        try {
            $phone = new PhoneNumber($request->phone, 'US'); // change US if your default country is different
            $normalizedPhone = $phone->formatE164();
        } catch (NumberParseException $e) {
            return [
                'data' => null,
                'message' => 'Invalid phone number',
                'code' => 422,
            ];
        }

        $credentials = [
            'phone' => $normalizedPhone,
            'password' => $request->password,
        ];

        if (! $token = JWTAuth::attempt($credentials)) {
            return [
                'data' => null,
                'message' => 'Unauthorized',
                'code' => 401,
            ];
        }

        $this->otpService->sendOtp($normalizedPhone);

        return [
            'data' => $this->respondWithToken($token),
            'message' => 'Login successful',
            'code' => 200,
        ];
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
        $logout = auth()->logout();

        return ['data' => $logout, 'message' => 'Successfully logged out', 'code' => 200];
    }

    /**
     * Refresh the JWT token.
     *
     * @return array
     */
    public function refresh()
    {
        $refresh = auth()->refresh();
        if ($refresh) {
            return ['data' => $this->respondWithToken($refresh), 'message' => 'refresh token suceess', 'code' => 200];
        }

        return ['data' => null, 'message' => 'Unable to refresh token', 'code' => 401];
    }
}
