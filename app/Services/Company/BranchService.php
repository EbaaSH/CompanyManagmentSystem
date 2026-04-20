<?php

namespace App\Services\Company;

use App\Models\Company\Branch;
use App\Models\Company\BranchTimeHistory;
use App\Models\Company\BranchWeekDay;
use App\Models\Company\Company;
use App\Models\Company\WeekDay;

class BranchService
{
    // Get all branches for a given company with pagination
    public function getBranchesByCompany($companyId)
    {
        $company = Company::find($companyId);
        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }
        $branches = $company->branches()->with([
            'branchTimeHistories.weekDays',
            'employees',
            'orders',
            'drivers',
            'branchTimeHistories',

        ])->paginate(10);

        return [
            'data' => $branches,
            'message' => 'Branches retrieved successfully',
            'code' => 200,
        ];

    }

    // Create a new branch with time histories and weekdays
    public function createBranchWithHistory($request, $companyId)
    {
        $company = Company::find($companyId);
        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }
        $branch = Branch::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'code' => $request->code,
            'address' => $request->address,
            'city' => $request->city,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'phone' => $request->phone,
            'is_active' => $request->is_active,
        ]);

        if ($request->has('weekly_schedule') && is_array($request->weekly_schedule)) {

            // dd($request->weekly_schedule);
            $operationDate = now()->toDateString();

            foreach ($request->weekly_schedule as $item) {
                $weekDay = WeekDay::where('day_name', $item['day'])->first();

                if (!$weekDay) {
                    return [
                        'data' => null,
                        'message' => 'Invalid week day: ' . $item['day'],
                        'code' => 400,
                    ];
                }

                $branchTimeHistory = BranchTimeHistory::create([
                    'branch_id' => $branch->id,
                    'opening_time' => $item['opening_time'] . ':00',
                    'closing_time' => $item['closing_time'] . ':00',
                    'operation_date' => $operationDate,
                ]);

                BranchWeekDay::create([
                    'branch_time_history_id' => $branchTimeHistory->id,
                    'week_day_id' => $weekDay->id,
                ]);
            }
        }

        return [
            'data' => $branch->load('branchTimeHistories.weekDays'),
            'message' => 'Branch created successfully',
            'code' => 201,
        ];
    }

    // Get a branch by ID
    public function getBranchById($companyId, $branchId)
    {
        $company = Company::find($companyId);
        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }

        $branchs = $company->branches()->where('id', $branchId)->first();
        if (!$branchs) {
            return [
                'data' => null,
                'message' => 'Branch not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $branchs,
            'message' => 'Branch found successfully',
            'code' => 200,
        ];
    }

    // Update a branch
    public function updateBranch($request, $companyId, $branchId)
    {
        $company = Company::find($companyId);
        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }

        $branch = $company->branches()->find($branchId);
        if (!$branch) {
            return [
                'data' => null,
                'message' => 'Branch not found',
                'code' => 404,
            ];
        }

        // Update branch data
        $branch->update([
            'name' => $request->name ?? $branch->name,
            'code' => $request->code ?? $branch->code,
            'address' => $request->address ?? $branch->address,
            'city' => $request->city ?? $branch->city,
            'latitude' => $request->latitude ?? $branch->latitude,
            'longitude' => $request->longitude ?? $branch->longitude,
            'phone' => $request->phone ?? $branch->phone,
            'is_active' => $request->is_active ?? $branch->is_active,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Replace Weekly Schedule (IMPORTANT)
        |--------------------------------------------------------------------------
        */

        $histories = BranchTimeHistory::where('branch_id', $branch->id)->get();

        foreach ($histories as $history) {
            BranchWeekDay::where('branch_time_history_id', $history->id)->delete();
        }

        BranchTimeHistory::where('branch_id', $branch->id)->delete();

        if ($request->has('weekly_schedule') && is_array($request->weekly_schedule)) {
            $operationDate = now()->toDateString();

            foreach ($request->weekly_schedule as $item) {
                $weekDay = WeekDay::where('day_name', $item['day'])->first();

                if (!$weekDay) {
                    continue;
                }

                $history = BranchTimeHistory::create([
                    'branch_id' => $branch->id,
                    'opening_time' => $operationDate . ' ' . $item['opening_time'] . ':00',
                    'closing_time' => $operationDate . ' ' . $item['closing_time'] . ':00',
                    'operation_date' => $operationDate,
                ]);

                BranchWeekDay::create([
                    'branch_time_history_id' => $history->id,
                    'week_day_id' => $weekDay->id,
                ]);
            }
        }

        return [
            'data' => $branch->load('branchTimeHistories.weekDays', 'branchTimeHistories'),
            'message' => 'Branch updated successfully',
            'code' => 200,
        ];
    }
}
