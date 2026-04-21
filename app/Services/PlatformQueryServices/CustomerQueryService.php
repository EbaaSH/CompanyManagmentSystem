<?php

namespace App\Services\PlatformQueryServices;

use App\Models\Customer\CustomerProfile;

class CustomerQueryService
{
    public function getCustomerById($customerId)
    {
        $user = auth()->user();
        $customer = CustomerProfile::query()
            ->forUserViaPermission($user)
            ->with(
                'user',
                'addresses', )
            ->find($customerId);
        if (! $customer) {
            return [
                'data' => $customer,
                'message' => 'customer not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $customer,
            'message' => 'customer retrevied successfully',
            'code' => 200,
        ];

    }

    public function getAllCustomers()
    {
        $user = auth()->user();
        $customer = CustomerProfile::query()
            ->forUserViaPermission($user)
            ->with(
                'user',
                'addresses', )
            ->paginate(10);

        return [
            'data' => $customer,
            'message' => 'customer retrevied successfully',
            'code' => 200,
        ];
    }
}
