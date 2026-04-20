<?php

// app/Policies/BranchPolicy.php

namespace App\Policies;

use App\Models\Company\Branch;
use App\Models\User;

class BranchPolicy
{
    // TECHNIQUE 1
    public function viewAny(User $user): bool
    {
        return true; // all roles have some level of branch access
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'company-manager']);
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->hasRole('branch-manager')) {
            return $branch->user_id === $user->id;
        }
        if ($user->hasRole('company-manager')) {
            return $branch->company_id === $user->ownedCompany->id;
        }

        return $user->hasRole('super-admin');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasAnyRole(['super-admin', 'company-manager']);
    }

    // TECHNIQUE 2

    public function viewAnyViaPermission(User $user): bool
    {
        return $user->can('branches.scope.all') || $user->can('branches.scope.active');
    }

    public function viewViaPermission(User $user, Branch $branch): bool
    {
        if ($user->can('branches.scope.active')) {
            return true;
        }

        if ($user->can('branches.scope.all')) {
            return true;
        }

        if ($user->can('branches.scope.company')) {
            return $user->ownedCompany
                && $branch->company_id === $user->ownedCompany->id;
        }

        if ($user->can('branches.scope.own')) {
            return $branch->user_id === $user->id;
        }

        return false;
    }

    public function createViaPermission(User $user): bool
    {
        return $user->can('branches.write');
    }

    public function updateViaPermission(User $user, Branch $branch): bool
    {
        if (! $user->can('branches.write')) {
            return false;
        }

        if ($user->can('branches.scope.all')) {
            return true;
        }

        if ($user->can('branches.scope.company')) {
            return $user->ownedCompany
                && $branch->company_id === $user->ownedCompany->id;
        }

        if ($user->can('branches.scope.own')) {
            return $branch->user_id === $user->id;
        }

        return false;
    }
}
