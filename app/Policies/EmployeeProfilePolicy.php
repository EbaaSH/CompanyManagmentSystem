<?php

namespace App\Policies;

use App\Models\Employee\EmployeeProfile;
use App\Models\User;

class EmployeeProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'employees.scope.all',
            'employees.scope.company',
            'employees.scope.branch',
        ]);
        // employees.scope.none → excluded, they cannot list
    }

    public function view(User $user, EmployeeProfile $employee): bool
    {
        return match (true) {
            $user->can('employees.scope.all') => true,
            $user->can('employees.scope.company') => $employee->company_id === $user->resolveCompanyId(),
            $user->can('employees.scope.branch') => $employee->branch_id === $user->resolveBranchId(),
            $user->can('employees.scope.own') => $employee->user_id === $user->id,
            default => false,
        };
    }

    // Company-manager and branch-manager create employees
    // public function create(User $user): bool
    // {
    //     return $user->can('employees.write');
    // }

    // public function update(User $user, EmployeeProfile $employee): bool
    // {
    //     if (! $user->can('employees.write')) {
    //         return false;
    //     }

    //     if ($user->can('employees.scope.company')) {
    //         return $employee->company_id === $user->resolveCompanyId();
    //     }

    //     if ($user->can('employees.scope.branch')) {
    //         return $employee->branch_id === $user->resolveBranchId();
    //     }

    //     return false;
    // }

    // public function delete(User $user, EmployeeProfile $employee): bool
    // {
    //     if (! $user->can('employees.write')) {
    //         return false;
    //     }

    //     // Company-manager can delete employees in their company
    //     if ($user->can('employees.scope.company')) {
    //         return $employee->company_id === $user->resolveCompanyId();
    //     }

    //     return false;
    // }
}
