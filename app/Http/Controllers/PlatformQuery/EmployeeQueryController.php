<?php

namespace App\Http\Controllers\PlatformQuery;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Employee\EmployeeProfile;
use App\Services\PlatformQueryServices\EmployeeQueryService;
use Throwable;

class EmployeeQueryController extends Controller
{
    protected $queryService;

    public function __construct(EmployeeQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index()
    {
        $user = auth()->user();
        $this->authorize('viewAny', EmployeeProfile::class);
        try {
            $data = $this->queryService->getAllEmployees();

            return Response::Paginate($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function show($id)
    {
        $employee = EmployeeProfile::find($id);
        if (! $employee) {
            return Response::Error(null, 'employee not found', 404);
        }
        $this->authorize('view', $employee);
        try {
            $data = $this->queryService->getEmployeeById($id);
            if ($data['code'] === 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}
