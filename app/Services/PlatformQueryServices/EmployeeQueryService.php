<?php

namespace App\Services\PlatformQueryServices;

use App\Models\Employee\EmployeeProfile;

class EmployeeQueryService
{
    public function getEmployeeById($employeeId)
    {
        $user = auth()->user();
        $employee = EmployeeProfile::query()
            ->ForUserViaPermission($user)
            ->with('user',
                'company',
                'branch',
                'jobTitle',
                'shift'
            )
            ->find($employeeId);
        if (! $employee) {
            return [
                'data' => $employee,
                'message' => 'employee not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $employee,
            'message' => 'employee retrevied succesfully',
            'code' => 200,
        ];
    }

    public function getAllEmployees()
    {
        $user = auth()->user();
        $employees = EmployeeProfile::query()
            ->forUserViaPermission($user)
            ->with(
                'user',
                'company',
                'branch',
                'jobTitle',
                'shift'
            )->paginate(10);

        return [
            'data' => $employees,
            'message' => 'data return successfully',
            'code' => 200,
        ];
    }
}
