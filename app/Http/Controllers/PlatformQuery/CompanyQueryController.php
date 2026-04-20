<?php

namespace App\Http\Controllers\PlatformQuery;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Services\PlatformQueryServices\CompanyQueryService;
use Illuminate\Http\Request;
use Throwable;

class CompanyQueryController extends Controller
{
    protected $queryService;

    public function __construct(CompanyQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $this->authorize('viewAnyViaPermission', \App\Models\Company\Company::class);
        try {
            $data = $this->queryService->getAllCompanies();

            return Response::Paginate($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $this->authorize('viewAnyViaPermission', \App\Models\Company\Company::class);
        try {
            $data = $this->queryService->getCompanyById($id);
            if ($data['code'] === 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }
            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

}
