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
        return true;
    }

    public function update(User $user, Delivery $delivery): bool
    {
        // Driver can update their own delivery status
        if ($user->hasRole('driver')) {
            return $delivery->driver_id === $user->driverProfile->id;
        }
        return $user->hasAnyRole(['company-manager', 'branch-manager', 'employee']);
    }

    // TECHNIQUE 2
    public function updateViaPermission(User $user, Delivery $delivery): bool
    {
        if (!$user->can('deliveries.write'))
            return false;

        // driver can only update their own delivery
        if ($user->can('deliveries.scope.own') && $user->driverProfile) {
            return $delivery->driver_id === $user->driverProfile->id;
        }
        return true;
    }
}