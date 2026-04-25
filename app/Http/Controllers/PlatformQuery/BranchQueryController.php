<?php

namespace App\Http\Controllers\PlatformQuery;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Company\Branch;
use App\Services\PlatformQueryServices\BranchQueryService;
use Throwable;

class BranchQueryController extends Controller
{
    protected $queryService;

    public function __construct(BranchQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index()
    {
        try {
            $this->authorize('viewAny', Branch::class);
            $data = $this->queryService->getAllBranches();
            if ($data['code'] !== 200) {
                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            return Response::Paginate($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $branch = Branch::query()
                ->forUserViaPermission($user)
                ->withTrashed()
                ->find($id);
            if (!$branch) {
                return Response::Error(null, 'branch not found', 404);
            }
            $this->authorize('view', $branch);
            $data = $this->queryService->getBranchById($id);
            if ($data['code'] === 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}
