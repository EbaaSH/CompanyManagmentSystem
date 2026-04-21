<?php

namespace App\Policies;

use App\Models\Customer\CustomerProfile;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.scope.all')
            || $user->can('customers.scope.company')
            || $user->can('customers.scope.branch');
    }

    public function view(User $user, CustomerProfile $customer): bool
    {
        if ($user->can('customers.scope.branch')) {
            return $customer->orders()
                ->where('branch_id', $user->resolveBranchId())
                ->exists();
        }

        if ($user->can('customers.scope.company')) {
            return $customer->orders()
                ->where('company_id', $user->resolveCompanyId())
                ->exists();
        }
        if ($user->can('customers.scope.own')) {
            return true;
        }

        return $user->can('customers.scope.all');
    }

    public function update(User $user, CustomerProfile $customer): bool
    {

        if ($user->can('customers.scope.branch')) {
            return $customer->orders()
                ->where('branch_id', $user->resolveBranchId())
                ->exists();
        }

        if ($user->can('customers.scope.company')) {
            return $customer->orders()
                ->where('company_id', $user->resolveCompanyId())
                ->exists();
        }
        if ($user->can('customers.scope.own')) {
            return true;
        }

        return $user->can('customers.scope.all');
    }
}
