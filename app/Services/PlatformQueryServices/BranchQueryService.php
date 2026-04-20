<?php

namespace App\Services\PlatformQueryServices;

use App\Models\Company\Branch;

class BranchQueryService
{
    public function getBranchById($branchId)
    {
        $user = auth()->user();
        $branch = Branch::query()
            ->ForUserViaPermission($user)
            ->with([
                'branchTimeHistories.weekDays',
                'branchTimeHistories',
                'company',
                'manager',
                'employees',
                'drivers',
                'employees.jobTitle',
                'employees.shift',
                'employees.user',
                'drivers.user',
            ])
            ->where('id', $branchId)
            ->first();
        if (! $branch) {
            return [
                'data' => null,
                'message' => 'branch not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $branch,
            'message' => 'branch retreived successfully',
            'code' => 200,
        ];
    }

    public function getAllBranches()
    {
        $user = auth()->user();
        $branch = Branch::query()
            ->ForUserViaPermission($user)
            ->with([
                'branchTimeHistories.weekDays',
                'branchTimeHistories',
                'company',
                'manager',
                'employees',
                'drivers',
                'employees.jobTitle',
                'employees.shift',
                'employees.user',
                'drivers.user',
            ])
            ->paginate(10);

        return [
            'data' => $branch,
            'message' => 'branch retreived successfully',
            'code' => 200,
        ];
    }
}
