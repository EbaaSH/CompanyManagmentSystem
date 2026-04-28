<?php

namespace App\Services\Customer;

use App\Models\Customer\CustomerAddress;
use App\Models\Customer\CustomerProfile;
use App\Models\User;
use App\Services\Auth\OTPService;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Propaganistas\LaravelPhone\PhoneNumber;

class CustomerService
{
    private OTPService $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function customerRegister($request)
    {
        $phone = new PhoneNumber($request->phone);

        $normalized = $phone->formatE164();
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $normalized,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('customer');

        $this->otpService->sendOtp($user->phone);

        $token = JWTAuth::fromUser($user);

        $customer = CustomerProfile::create([
            'user_id' => $user->id,
            'loyalty_points' => 0,
            'is_active' => $request->is_active ?? true,

        ]);

        foreach ($request->addresses as $address) {
            CustomerAddress::create([
                'customer_id' => $customer->id,
                'label' => $address['label'],
                'address_line' => $address['address_line'],
                'city' => $address['city'],
                'latitude' => $address['latitude'],
                'longitude' => $address['longitude'],
                'is_default' => $address['is_default'],
            ]);
        }

        $data =
            [
                'customer' => $customer->load('user', 'addresses'),
                'token' => $token,
            ];

        return [
            'data' => $data,
            'message' => 'Account created successfully',
            'code' => 201,
        ];
    }

    public function updateCustomer($request, $id)
    {
        $user = auth()->user();
        $customer = CustomerProfile::query()
            ->forUserViaPermission($user)
            ->with('user', 'addresses')
            ->find($id);

        if (! $customer) {
            return [
                'data' => null,
                'message' => 'customer not found',
                'code' => 404,
            ];
        }

        // // 1. Update user
        // $customer->user->update([
        //     'name' => $request->name,
        //     'email' => $request->email,
        //     'phone' => $request->phone,

        // ]);

        // 2. Update addresses (update or create)
        foreach ($request->addresses as $addressData) {

            CustomerAddress::updateOrCreate(
                ['id' => $addressData['id'] ?? null],
                [
                    'customer_id' => $customer->id,
                    'label' => $addressData['label'],
                    'address_line' => $addressData['address_line'],
                    'city' => $addressData['city'],
                    'latitude' => $addressData['latitude'],
                    'longitude' => $addressData['longitude'],
                    'is_default' => $addressData['is_default'] ?? false,
                ]
            );
        }

        $customer->fresh(['user', 'addresses']);

        return [
            'data' => $customer,
            'message' => 'customer profile updated successfully',
            'code' => 200,
        ];
    }
}
