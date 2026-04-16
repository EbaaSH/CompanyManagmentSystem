<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Create Permissions for Company
        Permission::create(['name' => 'view companies']);
        Permission::create(['name' => 'create companies']);
        Permission::create(['name' => 'update companies']);
        Permission::create(['name' => 'delete companies']);

        // Create Permissions for Branches
        Permission::create(['name' => 'view branches']);
        Permission::create(['name' => 'create branches']);
        Permission::create(['name' => 'update branches']);

        // Create Roles
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $companyAdminRole = Role::create(['name' => 'company_admin']);
        $branchManagerRole = Role::create(['name' => 'branch_manager']);

        // Assign Permissions to Roles
        $superAdminRole->givePermissionTo(Permission::all());
        $companyAdminRole->givePermissionTo(['view companies', 'create companies', 'update companies']);
        $branchManagerRole->givePermissionTo('view companies');

        // Assign Permissions to Roles
        $superAdminRole->givePermissionTo(Permission::all());
        $companyAdminRole->givePermissionTo(['view branches', 'create branches', 'update branches']);
        $branchManagerRole->givePermissionTo('view branches');
    }
}
