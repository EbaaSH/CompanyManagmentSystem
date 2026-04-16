<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\BranchCreateRequest;
use App\Http\Requests\Company\BranchUpdateRequest;
use App\Http\Responses\Response;
use App\Services\Company\BranchService;
use Illuminate\Support\Facades\DB;
use Throwable;

class BranchController extends Controller
{
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    /**
     * Get all branches of a company with pagination.
     */
    public function index($companyId)
    {
        try {
            $data = $this->branchService->getBranchesByCompany($companyId);
            if ($data['code'] == 200) {
                return Response::Paginate($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Create a new branch with transaction handling.
     */
    public function store(BranchCreateRequest $request, $companyId)
    {
        DB::beginTransaction();
        try {
            $data = $this->branchService->createBranchWithHistory($request, $companyId);
            if ($data['code'] == 201) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Get details of a single branch.
     */
    public function show($companyId, $branchId)
    {
        DB::beginTransaction();
        try {
            $data = $this->branchService->getBranchById($companyId, $branchId);

            if ($data['code'] == 200) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }
            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Update an existing branch.
     */
    public function update(BranchUpdateRequest $request, $companyId, $branchId)
    {
        DB::beginTransaction();
        try {
            $data = $this->branchService->updateBranch($request, $companyId, $branchId);
            if ($data['code'] == 200) {
                DB::commit();

                return Response::Success($data['data'], $data['message'], $data['code']);
            }
            DB::rollBack();

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }
}
