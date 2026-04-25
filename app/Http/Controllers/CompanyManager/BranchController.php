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

        try {
            $this->authorize('create', Branch::class);
            DB::beginTransaction();
            $data = $this->branchService->createBranch($request);

            if ($data['code'] !== 201) {
                DB::rollBack();
                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    public function update(UpdateBranchRequest $request, $branchId)
    {

        try {
            $branch = Branch::find($branchId);
            if (!$branch) {
                return Response::Error(null, 'branch not found', 404);
            }
            $this->authorize('update', $branch);
            DB::beginTransaction();
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
    public function delete($branchId)
    {
        try {
            $user = auth()->user();
            $branch = Branch::query()
                ->forUserViaPermission($user)
                ->find($branchId);
            if (!$branch) {
                return Response::Error(null, 'branch not found', 404);
            }
            $this->authorize('delete', $branch);
            DB::beginTransaction();
            $data = $this->branchService->deleteBranch($branchId);

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
    public function restore($branchId)
    {
        try {
            $user = auth()->user();
            $branch = Branch::query()
                ->forUserViaPermission($user)
                ->onlyTrashed()
                ->find($branchId);
            if (!$branch) {
                return Response::Error(null, 'branch not found', 404);
            }
            $this->authorize('delete', $branch);
            DB::beginTransaction();
            $data = $this->branchService->restoreBranch($branchId);

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
