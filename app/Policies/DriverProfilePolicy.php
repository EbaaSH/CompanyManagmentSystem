<?php

namespace App\Policies;

use App\Models\Driver\DriverProfile;
use App\Models\User;

class DriverProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'drivers.scope.all',
            'drivers.scope.company',
            'drivers.scope.branch',
            'drivers.scope.own',
        ]);
    }

    public function view(User $user, DriverProfile $driver): bool
    {
        return match (true) {
            $user->can('drivers.scope.all') => true,
            $user->can('drivers.scope.company') => $driver->company_id === $user->resolveCompanyId(),
            $user->can('drivers.scope.branch') => $driver->branch_id === $user->resolveBranchId(),
            $user->can('drivers.scope.own') => $driver->user_id === $user->id,
            default => false,
        };
    }

    // Company-manager and branch-manager create drivers
    public function create(User $user): bool
    {
        return $user->can('drivers.write');
    }

    public function update(User $user, DriverProfile $driver): bool
    {
        if (! $user->can('drivers.write')) {
            return false;
        }

        if ($user->can('drivers.scope.company')) {
            return $driver->company_id === $user->resolveCompanyId();
        }

        if ($user->can('drivers.scope.branch') && $user->ownedBranch) {
            return $driver->branch_id === $user->ownedBranch->id;
        }

        return false;
        // Note: driver updating their own location/availability is
        // handled directly in DeliveryService without a policy check
        // since it's always self-referential (driverProfile->update).
    }

    public function delete(User $user, DriverProfile $driver): bool
    {
        if (! $user->can('drivers.write')) {
            return false;
        }

        if ($user->can('drivers.scope.company')) {
            return $driver->company_id === $user->resolveCompanyId();
        }

        return false;
    }
}
