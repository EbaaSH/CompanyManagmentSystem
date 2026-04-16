<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Create Permissions
        Permission::create(['name' => 'view companies']);
        Permission::create(['name' => 'create companies']);
        Permission::create(['name' => 'update companies']);
        Permission::create(['name' => 'delete companies']);

        // Create Roles
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $companyAdminRole = Role::create(['name' => 'company_admin']);
        $branchManagerRole = Role::create(['name' => 'branch_manager']);

        // Assign Permissions to Roles
        $superAdminRole->givePermissionTo(Permission::all());
        $companyAdminRole->givePermissionTo(['view companies', 'create companies', 'update companies']);
        $branchManagerRole->givePermissionTo('view companies');
    }
}
