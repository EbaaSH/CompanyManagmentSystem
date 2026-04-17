<?php

namespace App\Services\Employee;

use App\Models\Company\Branch;
use App\Models\Employee\EmployeeProfile;
use App\Models\User;
use Hash;

class EmployeeService
{
    public function createEmployee($request, $branch_id)
    {
        // 1. Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);
        $user->update(['phone_verified_at' => now()]);
        // 2. Assign Role
        $user->assignRole($request->role ?? 'branch_manager');

        $branch = Branch::find($branch_id);
        if (! $branch) {
            return [
                'data' => null,
                'message' => 'Branch not found',
                'code' => 404,
            ];
        }
        $company_id = $branch ? $branch->company_id : null;
        // 3. Create Employee Profile
        $employee = EmployeeProfile::create([
            'user_id' => $user->id,
            'company_id' => $company_id,
            'branch_id' => $branch_id,
            'job_title_id' => $request->job_title_id,
            'shift_id' => $request->shift_id,
            'hire_date' => $request->hire_date,
            'is_active' => true,
        ]);

        return [
            'data' => $employee->load(['user', 'company', 'branch']),
            'message' => 'Employee created successfully',
            'code' => 201,
        ];
    }

    public function getEmployee($branchId, $employeeId)
    {
        $branch = Branch::find($branchId);
        if (! $branch) {
            return [
                'data' => null,
                'message' => 'Branch not found',
                'code' => 404,
            ];
        }
        $employee = $branch->employees()->find($employeeId)->with(['user', 'company', 'branch'])->first();

        if (! $employee) {
            return [
                'data' => [],
                'message' => 'Employee not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $employee,
            'message' => 'Employee retrieved successfully',
            'code' => 200,
        ];
    }

    public function updateEmployee($request, $branchId, $employeeId)
    {
        $branch = Branch::find($branchId);
        if (! $branch) {
            return [
                'data' => null,
                'message' => 'Branch not found',
                'code' => 404,
            ];
        }
        $company_id = $branch ? $branch->company_id : null;
        $employee = $branch->employees()->find($employeeId)->with('user')->first();

        if (! $employee) {
            return [
                'data' => [],
                'message' => 'Employee not found',
                'code' => 404,
            ];
        }

        // Update user
        $employee->user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        // Update profile
        $employee->update([
            'branch_id' => $branchId,
            'company_id' => $company_id,
            'job_title_id' => $request->job_title_id,
            'shift_id' => $request->shift_id,
            'hire_date' => $request->hire_date,
            'is_active' => $request->is_active,
        ]);

        return [
            'data' => $employee->load(['user', 'company', 'branch']),
            'message' => 'Employee updated successfully',
            'code' => 200,
        ];
    }

    /**
     * Assign branch (separate endpoint)
     */
    public function assignBranch($branchId, $employeeId)
    {
        $branch = Branch::find($branchId);
        if (! $branch) {
            return [
                'data' => null,
                'message' => 'Branch not found',
                'code' => 404,
            ];
        }

        $employee = $branch->employees()->find($employeeId)->with(['user', 'company', 'branch'])->first();

        if (! $employee) {
            return [
                'data' => [],
                'message' => 'Employee not found',
                'code' => 404,
            ];
        }

        $employee->update([
            'branch_id' => $branchId,
        ]);

        return [
            'data' => $employee->load(['user', 'company', 'branch']),
            'message' => 'Branch assigned successfully',
            'code' => 200,
        ];
    }
}
