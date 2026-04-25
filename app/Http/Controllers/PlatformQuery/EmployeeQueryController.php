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
        try {
            $this->authorize('viewAny', EmployeeProfile::class);
            $data = $this->queryService->getAllEmployees();

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
            $employee = EmployeeProfile::query()->ForUserViaPermission($user)->find($id);
            if (!$employee) {
                return Response::Error(null, 'employee not found', 404);
            }
            $this->authorize('view', $employee);
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
