<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // -------------------------------
        // Company Permissions
        // -------------------------------
        $companyPermissions = [
            'view companies',
            'create companies',
            'update companies',
            'delete companies',
        ];

        // -------------------------------
        // Branch Permissions
        // -------------------------------
        $branchPermissions = [
            'view branches',
            'create branches',
            'update branches',
        ];

        // -------------------------------
        // Employee Permissions
        // -------------------------------
        $employeePermissions = [
            'view employees',
            'create employees',
            'update employees',
            'assign employee branch',
        ];

        // Create all permissions
        foreach (array_merge(
            $companyPermissions,
            $branchPermissions,
            $employeePermissions
        ) as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // -------------------------------
        // Roles
        // -------------------------------
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $companyAdmin = Role::firstOrCreate(['name' => 'company_admin']);
        $branchManager = Role::firstOrCreate(['name' => 'branch_manager']);

        // -------------------------------
        // Assign Permissions
        // -------------------------------

        // Super Admin → everything
        $superAdmin->syncPermissions(Permission::all());

        // Company Admin
        $companyAdmin->syncPermissions(array_merge(
            $companyPermissions,
            $branchPermissions,
            $employeePermissions
        ));

        // Branch Manager (limited)
        $branchManager->syncPermissions([
            'view companies',
            'view branches',
            'view employees',
        ]);
    }
}
