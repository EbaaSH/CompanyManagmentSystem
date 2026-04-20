<?php
// app/Policies/MenuPolicy.php

namespace App\Policies;


use App\Models\Menu\Menu;
use App\Models\User;

class MenuPolicy
{
    // TECHNIQUE 1
    public function viewAny(User $user): bool
    {
        return true; // all roles except driver have menu access
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['company-manager', 'branch-manager']);
    }

    public function update(User $user, Menu $menu): bool
    {
        if ($user->hasRole('branch-manager')) {
            return $menu->branch_id === $user->ownedBranch->id;
        }
        if ($user->hasRole('company-manager')) {
            return $menu->branch->company_id === $user->ownedCompany->id;
        }
        return false;
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $user->hasAnyRole(['company-manager', 'branch-manager']);
    }

    // TECHNIQUE 2
    public function createViaPermission(User $user): bool
    {
        return $user->can('menus.write');
    }

    public function updateViaPermission(User $user, Menu $menu): bool
    {
        if (!$user->can('menus.write'))
            return false;

        if ($user->can('menus.scope.branch')) {
            return $menu->branch_id === $user->resolveBranchId();
        }
        if ($user->can('menus.scope.company')) {
            return $menu->branch->company_id === $user->resolveCompanyId();
        }
        return $user->can('menus.scope.all');
    }
}