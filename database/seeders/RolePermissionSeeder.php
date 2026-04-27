<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'api'; // change to 'web' if using session auth

        // ────────────────────────────────────────────────────────────────
        // PART A — Permissions
        // Pattern: "{resource}.scope.{level}" for data filtering
        //          "{resource}.write"          for mutation rights
        //
        // Scope levels:
        //   all         → everything in the system
        //   company     → scoped to the user's company
        //   branch      → scoped to the user's branch
        //   own         → scoped to the user's own record/assignment
        //   active      → public browsing (active records)
        //   active_now  → public browsing with time-window filter
        //   assigned    → driver's assigned orders only
        //   none        → explicit no-access marker
        // ────────────────────────────────────────────────────────────────

        $permissions = [
            // Companies
            'companies.scope.all',
            'companies.scope.own',
            'companies.scope.active',
            'companies.write',
            'companies.update',
            'companies.delete',

            // Branches
            'branches.scope.all',
            'branches.scope.company',
            'branches.scope.own',
            'branches.scope.active',
            'branches.write',
            'branches.update',
            'branches.delete',

            // Employees
            'employees.scope.all',
            'employees.scope.company',
            'employees.scope.branch',
            'employees.scope.own',
            // 'employees.scope.none',
            'employees.write',
            'employees.update',
            'employees.delete',

            // Drivers
            'drivers.scope.all',
            'drivers.scope.company',
            'drivers.scope.branch',
            'drivers.scope.own',
            'drivers.write',
            'drivers.update',
            'drivers.delete',

            // Customers
            'customers.scope.all',
            'customers.scope.company',
            'customers.scope.branch',
            'customers.scope.own',

            // Orders
            'orders.scope.all',
            'orders.scope.company',
            'orders.scope.branch',
            'orders.scope.assigned',
            'orders.scope.own',
            'orders.write',
            'orders.delete',
            'orders.cancel',
            'orders.markPreparing',
            'orders.markReady',
            'orders.reject',

            // Deliveries
            'deliveries.scope.all',
            'deliveries.scope.company',
            'deliveries.scope.branch',
            'deliveries.scope.own',
            'deliveries.write',

            // Menus
            'menus.scope.all',
            'menus.scope.company',
            'menus.scope.branch',
            'menus.scope.active_now',
            'menus.write',
            'menus.update',
            'menus.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        // ────────────────────────────────────────────────────────────────
        // PART B — Roles and their permission sets
        // ────────────────────────────────────────────────────────────────

        // ── super-admin ──────────────────────────────────────────────
        // Read-only across all data. Can manage (write) companies only.
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guard])
            ->syncPermissions([
                'companies.scope.all',
                'companies.write',
                'companies.update',
                'companies.delete',        // only role that can create/delete companies
                'branches.scope.all',
                // no branches.write
                'employees.scope.all',
                'drivers.scope.all',
                'customers.scope.all',
                'orders.scope.all',
                // no orders.write — read-only
                'deliveries.scope.all',
                // no deliveries.write
                'menus.scope.all',
                // no menus.write
            ]);

        // ── company-manager ──────────────────────────────────────────
        // Anchored via companies.user_id.
        // Full access within their company. No visibility outside it.
        Role::firstOrCreate(['name' => 'company-manager', 'guard_name' => $guard])
            ->syncPermissions([
                'companies.scope.own',
                // branches
                'branches.scope.company',
                'branches.write',
                'branches.update',
                'branches.delete',
                // employees
                'employees.scope.company',
                'employees.write',
                'employees.update',
                'employees.delete',

                'drivers.scope.company',
                'drivers.write',
                'drivers.update',
                'drivers.delete',

                'customers.scope.company',

                'orders.scope.company',
                'orders.write',

                'deliveries.scope.company',
                'deliveries.write',

                'menus.scope.company',
                // 'menus.write',
                'menus.update',
                'menus.delete',
            ]);

        // ── branch-manager ───────────────────────────────────────────
        // Anchored via branches.user_id.
        // Full access within their branch only.
        Role::firstOrCreate(['name' => 'branch-manager', 'guard_name' => $guard])
            ->syncPermissions([
                'branches.scope.own',

                'employees.scope.branch',

                'drivers.scope.branch',

                'customers.scope.branch',

                'orders.scope.branch',
                'orders.write',
                'orders.markPreparing',
                'orders.markReady',
                'orders.reject',

                'deliveries.scope.branch',
                'deliveries.write',

                'menus.scope.branch',
                'menus.write',
                'menus.update',
                'menus.delete',

                'orders.delete',
            ]);

        // ── employee ─────────────────────────────────────────────────
        // Anchored via employee_profiles.
        // Can see orders, deliveries, customers, menus for their branch.
        // Cannot list other employees.
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => $guard])
            ->syncPermissions([
                'employees.scope.own',     // no employee directory access
                'drivers.scope.branch',
                'customers.scope.branch',
                'orders.scope.branch',
                'orders.write',
                'orders.markPreparing',
                'orders.markReady',
                'orders.reject',
                'deliveries.scope.branch',
                'menus.scope.branch',
                'orders.delete',
            ]);

        // ── driver ───────────────────────────────────────────────────
        // Anchored via driver_profiles (reassignable branch).
        // Read-only on assigned orders. Own deliveries only.
        Role::firstOrCreate(['name' => 'driver', 'guard_name' => $guard])
            ->syncPermissions([
                'branches.scope.own',       // can see their current branch
                'employees.scope.branch',   // coworkers in the branch
                'drivers.scope.own',        // own profile only
                'orders.scope.assigned',    // read-only — no orders.write
                'deliveries.scope.own',
                'deliveries.write',         // driver can update their own delivery status
            ]);

        // ── customer ─────────────────────────────────────────────────
        // Can browse all active companies/branches/menus.
        // Can only see and place their own orders.
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => $guard])
            ->syncPermissions([
                'companies.scope.active',
                'branches.scope.active',
                'customers.scope.own',
                'orders.scope.own',
                'orders.write',             // customers place orders
                'deliveries.scope.own',
                'menus.scope.active_now',
                'orders.delete',
                'orders.cancel',
            ]);
    }
}
