<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $companyAdminRole = Role::create(['name' => 'company_admin']);
        $branchManagerRole = Role::create(['name' => 'branch_manager']);
        $employeeRole = Role::create(['name' => 'employee']);
        $driverRole = Role::create(['name' => 'driver']);
        $customerRole = Role::create(['name' => 'customer']);

        // Create permissions
        $manageCompaniesPermission = Permission::create(['name' => 'manage companies']);
        $manageBranchesPermission = Permission::create(['name' => 'manage branches']);
        $manageEmployeesPermission = Permission::create(['name' => 'manage employees']);
        $manageDriversPermission = Permission::create(['name' => 'manage drivers']);
        $viewOrdersPermission = Permission::create(['name' => 'view orders']);
        $manageOrdersPermission = Permission::create(['name' => 'manage orders']);
        $placeOrdersPermission = Permission::create(['name' => 'place orders']);
        $viewMenusPermission = Permission::create(['name' => 'view menus']);
        $confirmOrdersPermission = Permission::create(['name' => 'confirm orders']);
        $assignDriverPermission = Permission::create(['name' => 'assign driver']);

        // Assign permissions to roles
        $superAdminRole->givePermissionTo([
            $manageCompaniesPermission,
            $manageBranchesPermission,
            $manageEmployeesPermission,
            $manageDriversPermission,
            $viewOrdersPermission,
            $manageOrdersPermission,
            $placeOrdersPermission,
            $viewMenusPermission,
        ]);

        $companyAdminRole->givePermissionTo([
            $manageBranchesPermission,
            $manageEmployeesPermission,
            $manageDriversPermission,
            $viewOrdersPermission,
        ]);

        $branchManagerRole->givePermissionTo([
            $viewOrdersPermission,
            $manageOrdersPermission,
            $confirmOrdersPermission,
        ]);

        $employeeRole->givePermissionTo([
            $viewOrdersPermission,
            $manageOrdersPermission,
        ]);

        $driverRole->givePermissionTo([
            $viewOrdersPermission,
            $assignDriverPermission,
        ]);

        $customerRole->givePermissionTo([
            $placeOrdersPermission,
            $viewOrdersPermission,
        ]);
    }
}
