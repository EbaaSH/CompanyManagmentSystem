<?php

namespace App\Services\CompanyManager;

use App\Models\Company\Branch;
use App\Models\Company\BranchTimeHistory;
use App\Models\Company\BranchWeekDay;
use App\Models\Company\WeekDay;
use App\Models\Driver\DriverProfile;
use App\Models\Employee\EmployeeProfile;
use App\Models\User;

class BranchService
{
    public function createBranch($request)
    {
        $user = auth()->user();
        $company = $user->ownedCompany;
        if (!$company) {
            return [
                'data' => null,
                'message' => 'Company not found',
                'code' => 404,
            ];
        }
        $user = User::create([
            'name' => $request->manager_name,
            'email' => $request->manager_email,
            'password' => $request->password,
            'phone' => $request->manager_phone,
        ]);

        $user->assignRole('branch-manager');

        $user->update(['phone_verified_at' => now()]);
        $companyId = $company->id;
        $branch = Branch::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
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
            // $operationDate = now()->toDateString();

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
                    // 'operation_date' => $operationDate,
                ]);

                BranchWeekDay::create([
                    'branch_time_history_id' => $branchTimeHistory->id,
                    'week_day_id' => $weekDay->id,
                ]);
            }
        }

        if ($request->has('employees') && is_array($request->employees)) {
            foreach ($request->employees as $employee) {
                $employeeUser = User::create([
                    'name' => $employee['name'],
                    'email' => $employee['email'],
                    'password' => $employee['password'],
                    'phone' => $employee['phone'],
                ]);

                $employeeUser->update(['phone_verified_at' => now()]);

                $employeeUser->assignRole('employee');

                $employeeProfile = $employeeUser->employeeProfile()->create([
                    'user_id' => $employeeUser->id,
                    'branch_id' => $branch->id,
                    'company_id' => $companyId,
                    'job_title_id' => $employee['job_title_id'] ?? null,
                    'shift_id' => $employee['shift_id'] ?? null,
                    'hire_date' => $employee['hire_date'] ?? null,
                    'is_active' => $employee['is_active'] ?? true,
                ]);
            }
        }
        if ($request->has('drivers') && is_array($request->drivers)) {
            foreach ($request->drivers as $driver) {
                $driverUser = User::create([
                    'name' => $driver['name'],
                    'email' => $driver['email'],
                    'password' => $driver['password'],
                    'phone' => $driver['phone'],
                ]);

                $driverUser->update(['phone_verified_at' => now()]);

                $driverUser->assignRole('driver');

                $driverProfile = $driverUser->driverProfile()->create([
                    'user_id' => $driverUser->id,
                    'branch_id' => $branch->id,
                    'company_id' => $companyId,
                    'vehicle_type' => $driver['vehicle_type'] ?? null,
                    'plate_number' => $driver['plate_number'] ?? null,
                    'availability_status' => $driver['availability_status'] ?? 'offline',
                    'current_latitude' => $request->latitude ?? $branch->latitude,
                    'current_longitude' => $request->longitude ?? $branch->longitude,
                    'is_active' => $driver['is_active'] ?? true,
                ]);
            }
        }

        return [
            'data' => $branch->load(
                'branchTimeHistories.weekDays',
                'branchTimeHistories',
                'company',
                'manager',
                'employees',
                'drivers',
                'employees.jobTitle',
                'employees.shift',
                'employees.user',
                'drivers.user'
            ),
            'message' => 'Branch created successfully',
            'code' => 201,
        ];
    }

    public function updateBranch($request, $branchId)
    {
        $user = auth()->user();
        $branch = Branch::query()->forUserViaPermission($user)->find($branchId);
        if (!$branch) {
            return [
                'data' => null,
                'message' => 'Branch not found',
                'code' => 404,
            ];
        }

        $branchManager = $branch->manager;

        $branchManager->update([
            'name' => $request->manager_name ?? $branchManager->name,
            'email' => $request->manager_email ?? $branchManager->email,
            'phone' => $request->manager_phone ?? $branchManager->phone,
            'password' => $request->password ?? $branchManager->password,
        ]);

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
                    'opening_time' => $item['opening_time'] . ':00',
                    'closing_time' => $item['closing_time'] . ':00',
                    // 'operation_date' => $operationDate,
                ]);

                BranchWeekDay::create([
                    'branch_time_history_id' => $history->id,
                    'week_day_id' => $weekDay->id,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Replace employees (IMPORTANT)
        |--------------------------------------------------------------------------
        */

        if ($request->has('employees') && is_array($request->employees)) {

            $existingEmployeeIds = $branch->employees()->pluck('user_id')->toArray();
            $incomingEmployeeIds = [];

            foreach ($request->employees as $employee) {

                // UPDATE
                if (!empty($employee['id'])) {

                    $employeeUser = User::find($employee['id']);
                    if (!$employeeUser) {
                        continue;
                    }

                    $employeeUser->update([
                        'name' => $employee['name'] ?? $employeeUser->name,
                        'email' => $employee['email'] ?? $employeeUser->email,
                        'password' => !empty($employee['password']) ? bcrypt($employee['password']) : $employeeUser->password,
                        'phone' => $employee['phone'] ?? $employeeUser->phone,
                    ]);

                    $employeeUser->employeeProfile()->update([
                        'job_title_id' => $employee['job_title_id'] ?? null,
                        'shift_id' => $employee['shift_id'] ?? null,
                        'hire_date' => $employee['hire_date'] ?? null,
                        'is_active' => $employee['is_active'] ?? true,
                    ]);

                    $incomingEmployeeIds[] = $employeeUser->id;
                }

                // CREATE
                else {

                    $employeeUser = User::create([
                        'name' => $employee['name'],
                        'email' => $employee['email'],
                        'password' => bcrypt($employee['password']),
                        'phone' => $employee['phone'],
                    ]);

                    $employeeUser->update(['phone_verified_at' => now()]);

                    $employeeUser->assignRole('employee');

                    $employeeUser->employeeProfile()->create([
                        'user_id' => $employeeUser->id,
                        'branch_id' => $branch->id,
                        'company_id' => $branch->company_id,
                        'job_title_id' => $employee['job_title_id'] ?? null,
                        'shift_id' => $employee['shift_id'] ?? null,
                        'hire_date' => $employee['hire_date'] ?? null,
                        'is_active' => $employee['is_active'] ?? true,
                    ]);

                    $incomingEmployeeIds[] = $employeeUser->id;
                }
            }

            // OPTIONAL: Deactivate removed employees
            $toDeactivate = array_diff($existingEmployeeIds, $incomingEmployeeIds);

            if (!empty($toDeactivate)) {
                EmployeeProfile::whereIn('user_id', $toDeactivate)
                    ->update(['is_active' => false]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Replace drivers (IMPORTANT)
        |--------------------------------------------------------------------------
        */

        if ($request->has('drivers') && is_array($request->drivers)) {

            $existingDriverIds = $branch->drivers()->pluck('user_id')->toArray();
            $incomingDriverIds = [];

            foreach ($request->drivers as $driver) {

                // UPDATE
                if (!empty($driver['id'])) {

                    $driverUser = User::find($driver['id']);
                    if (!$driverUser) {
                        continue;
                    }

                    $driverUser->update([
                        'name' => $driver['name'] ?? $driverUser->name,
                        'email' => $driver['email'] ?? $driverUser->email,
                        'password' => !empty($driver['password']) ? bcrypt($driver['password']) : $driverUser->password,
                        'phone' => $driver['phone'] ?? $driverUser->phone,
                    ]);

                    $driverUser->driverProfile()->update([
                        'vehicle_type' => $driver['vehicle_type'] ?? null,
                        'plate_number' => $driver['plate_number'] ?? null,
                        'availability_status' => $driver['availability_status'] ?? 'offline',
                        'current_latitude' => $driver['current_latitude'] ?? $branch->latitude,
                        'current_longitude' => $driver['current_longitude'] ?? $branch->longitude,
                        'is_active' => $driver['is_active'] ?? true,
                    ]);

                    $incomingDriverIds[] = $driverUser->id;
                }

                // CREATE
                else {

                    $driverUser = User::create([
                        'name' => $driver['name'],
                        'email' => $driver['email'],
                        'password' => bcrypt($driver['password']),
                        'phone' => $driver['phone'],
                    ]);

                    $driverUser->update(['phone_verified_at' => now()]);

                    $driverUser->assignRole('driver');

                    $driverUser->driverProfile()->create([
                        'user_id' => $driverUser->id,
                        'branch_id' => $branch->id,
                        'company_id' => $branch->company_id,
                        'vehicle_type' => $driver['vehicle_type'] ?? null,
                        'plate_number' => $driver['plate_number'] ?? null,
                        'availability_status' => $driver['availability_status'] ?? 'offline',
                        'current_latitude' => $driver['current_latitude'] ?? $branch->latitude,
                        'current_longitude' => $driver['current_longitude'] ?? $branch->longitude,
                        'is_active' => $driver['is_active'] ?? true,
                    ]);

                    $incomingDriverIds[] = $driverUser->id;
                }
            }

            // OPTIONAL: Deactivate removed drivers
            $toDeactivate = array_diff($existingDriverIds, $incomingDriverIds);

            if (!empty($toDeactivate)) {
                DriverProfile::whereIn('user_id', $toDeactivate)
                    ->update(['is_active' => false]);
            }
        }

        return [
            'data' => $branch->load(
                'branchTimeHistories.weekDays',
                'branchTimeHistories',
                'company',
                'manager',
                'employees',
                'drivers',
                'employees.jobTitle',
                'employees.shift',
                'employees.user',
                'drivers.user'
            ),
            'message' => 'Branch updated successfully',
            'code' => 200,
        ];
    }
    public function deleteBranch($branchId)
    {
        $user = auth()->user();
        $branch = Branch::query()->forUserViaPermission($user)->find($branchId);
        if (!$branch) {
            return [
                'data' => null,
                'message' => 'Branch not found',
                'code' => 404,
            ];
        }
        $branch->delete();
        return [
            'data' => null,
            'message' => 'Branch deleted successfully',
            'code' => 200,
        ];
    }
    public function restoreBranch($branchId)
    {
        $user = auth()->user();
        $branch = Branch::query()->forUserViaPermission($user)->onlyTrashed()->find($branchId);
        if (!$branch) {
            return [
                'data' => null,
                'message' => 'Branch not found',
                'code' => 404,
            ];
        }
        $branch->restore();
        return [
            'data' => null,
            'message' => 'Branch restored successfully',
            'code' => 200,
        ];
    }
}
