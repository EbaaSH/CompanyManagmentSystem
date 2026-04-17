<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\EmployeeCreateRequest;
use App\Http\Requests\Employee\EmployeeUpdateRequest;
use App\Http\Responses\Response;
use App\Services\Employee\EmployeeService;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function store(EmployeeCreateRequest $request, $branchId)
    {
        DB::beginTransaction();
        try {
            $data = $this->employeeService->createEmployee($request, $branchId);

            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    public function show($branchId, $employeeId)
    {
        try {
            $data = $this->employeeService->getEmployee($branchId, $employeeId);

            if ($data['code'] != 200) {
                return Response::Error([], $data['message'], $data['code']);
            }

            return Response::Success($data['data'], $data['message'], $data['code']);

        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function update(EmployeeUpdateRequest $request, $branchId, $employeeId)
    {
        DB::beginTransaction();
        try {
            $data = $this->employeeService->updateEmployee($request, $branchId, $employeeId);

            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    public function assignBranch($branchId, $employeeId)
    {
        DB::beginTransaction();
        try {
            $data = $this->employeeService->assignBranch($branchId, $employeeId);
            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }
}
