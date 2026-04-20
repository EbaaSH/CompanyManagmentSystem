<?php

namespace App\Http\Controllers\CompanyManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyManager\StoreBranchRequest;
use App\Http\Requests\CompanyManager\UpdateBranchRequest;
use App\Http\Responses\Response;
use App\Models\Company\Branch;
use App\Services\CompanyManager\BranchService;
use Illuminate\Support\Facades\DB;
use Throwable;

class BranchController extends Controller
{
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function store(StoreBranchRequest $request)
    {

        $this->authorize('createViaPermission', Branch::class);
        DB::beginTransaction();
        try {
            $data = $this->branchService->createBranch($request);

            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    public function update(UpdateBranchRequest $request, $branchId)
    {
        $branch = Branch::find($branchId);
        if (! $branch) {
            return Response::Error(null, 'branch not found', 404);
        }
        $this->authorize('updateViaPermission', $branch);
        DB::beginTransaction();

        try {
            $data = $this->branchService->updateBranch($request, $branchId);

            if ($data['code'] !== 200) {
                DB::rollBack();

                return Response::Error($data['data'], $data['message'], $data['code']);
            }

            DB::commit();

            return Response::Success($data['data'], $data['message']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }
}
