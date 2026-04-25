<?php
// app/Policies/CompanyPolicy.php

namespace App\Policies;


use App\Models\Company\Company;
use App\Models\User;

class CompanyPolicy
{
    // TECHNIQUE 1 — Role names
    // public function viewAny(User $user): bool
    // {
    //     return $user->hasAnyRole(['super-admin', 'company-manager', 'customer']);
    // }

    // public function view(User $user, Company $company): bool
    // {
    //     return $user->hasAnyRole(['super-admin', 'company-manager', 'customer']);
    // }

    // public function create(User $user): bool
    // {
    //     return $user->hasRole('super-admin');
    // }

    // public function update(User $user, Company $company): bool
    // {
    //     // company-manager can update only their own company
    //     if ($user->hasRole('company-manager')) {
    //         return $company->user_id === $user->id;
    //     }
    //     return $user->hasRole('super-admin');
    // }

    // public function delete(User $user, Company $company): bool
    // {
    //     return $user->hasRole('super-admin');
    // }

    // TECHNIQUE 2 — Permissions
    public function viewAnyViaPermission(User $user): bool
    {
        return $user->hasAnyPermission([
            'companies.scope.all',
            'companies.scope.own',
            'companies.scope.active',
        ]);
    }

    public function createViaPermission(User $user): bool
    {
        return $user->can('companies.write');
    }

    public function updateViaPermission(User $user, Company $company): bool
    {
        if ($user->can('companies.write') && $user->can('companies.update'))
            return true;
        return false;
    }

    public function deleteViaPermission(User $user, Company $company): bool
    {
        return $user->can('companies.delete');
    }
}