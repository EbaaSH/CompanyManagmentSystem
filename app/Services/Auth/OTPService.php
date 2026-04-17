<?php

namespace App\Services\Auth;

use App\Models\OTP;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OTPService
{
    private string $smsUrl;

    private string $smsApiKey;

    private int $otpExpiryMinutes = 10;

    private int $otpLength = 5;

    public function __construct()
    {
        $this->smsUrl = config('services.traccer.url');
        $this->smsApiKey = config('services.traccer.key');
    }

    public function sendOtp(string $phone): bool
    {
        $otp = $this->createOtp($phone);

        $message = sprintf(
            'Your OTP code for %s is: %s. Do not share it with anyone.',
            config('app.name'),
            $otp
        );

        return $this->sendSms($phone, $message)->successful();
    }

    public function verifyOtp(string $phone, string $code)
    {
        $otp = OTP::query()
            ->where('phone', $phone)
            ->where('otp', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            throw new \Exception('OTP is invalid or expired');
        }

        $otp->update(['used' => true]);

        return $otp;
    }

    public function sendMessage(string $phone, string $message): bool
    {
        return $this->sendSms($phone, $message)->successful();
    }

    private function sendSms(string $phone, string $message): Response
    {
        Log::info('[SMS] Sending message', [
            'to' => $phone,
        ]);

        $response = Http::withHeaders([
            'Authorization' => $this->smsApiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->withOptions([
                'verify' => false,  // This disables SSL certificate verification
            ])
            ->post($this->smsUrl, [
                'to' => $phone,
                'message' => $message,
            ]);

        Log::info('[SMS] Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $response;
    }

    private function createOtp(string $phone): string
    {
        $otp = (string) random_int(
            10 ** ($this->otpLength - 1),
            (10 ** $this->otpLength) - 1
        );

        OTP::query()->create([
            'phone' => $phone,
            'otp' => $otp,
            'used' => false,
            'expires_at' => now()->addMinutes($this->otpExpiryMinutes),
        ]);

        Log::info('[OTP] OTP created', ['phone' => $phone]);

        return $otp;
    }

    // =======================================================

    public function verify($request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'otp' => ['required', 'string'],
        ]);

        if (! $user) {
            return [
                'data' => null,
                'message' => 'User not authenticated',
                'code' => 401,
            ];
        }

        $result = $this->verifyOtp(
            $user['phone'],
            $data['otp']);
        $user->update(['phone_verified_at' => now()]);

        return [
            'data' => $result,
            'message' => 'OTP verified successfully',
            'code' => 200,
        ];

    }

    public function resendCode()
    {
        $user = auth()->user();
        if (! $user) {
            return [
                'data' => null,
                'message' => 'User not authenticated',
                'code' => 401,
            ];
        }
        $this->sendOtp($user['phone']);

        return [
            'data' => null,
            'message' => 'OTP resent successfully',
            'code' => 200,
        ];
    }
}
