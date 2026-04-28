<?php
// app/Policies/DeliveryPolicy.php

namespace App\Policies;


use App\Models\Delivery\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    // TECHNIQUE 1

    public function viewAny(User $user): bool
    {
        return $user->can('deliveries.scope.all')
            || $user->can('deliveries.scope.company')
            || $user->can('deliveries.scope.branch')
            || $user->can('deliveries.scope.own');
    }

    public function view(User $user, Delivery $delivery): bool
    {
        // 🔵 Full access
        if ($user->can('deliveries.scope.all')) {
            return true;
        }

        // 🟢 Company scope
        if ($user->can('deliveries.scope.company')) {
            return $delivery->order->company_id === $user->resolveCompanyId();
        }

        // 🟡 Branch scope
        if ($user->can('deliveries.scope.branch')) {
            return $delivery->order->branch_id === $user->resolveBranchId();
        }

        // 🔴 Own scope
        if ($user->can('deliveries.scope.own')) {

            // Driver → own deliveries
            if ($user->driverProfile) {
                return $delivery->driver_id === $user->driverProfile->id;
            }

            // Customer → deliveries of their orders
            if ($user->customerProfile) {
                return $delivery->order->customer_id === $user->customerProfile->id;
            }
        }

        return false;
    }

    // TECHNIQUE 2
    public function accept(User $user, Delivery $delivery)
    {
        return $user->can('deliveries.accept');
    }
    public function reject(User $user, Delivery $delivery)
    {
        return $user->can('deliveries.reject');
    }
    public function pickup(User $user, Delivery $delivery)
    {
        return $user->can('deliveries.pickup');
    }
    public function deliver(User $user, Delivery $delivery)
    {
        return $user->can('deliveries.deliver');
    }
    public function fail(User $user, Delivery $delivery)
    {
        return $user->can('deliveries.fail');
    }
}