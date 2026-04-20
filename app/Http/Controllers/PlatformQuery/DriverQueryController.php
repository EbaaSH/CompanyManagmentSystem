<?php

namespace App\Http\Controllers\PlatformQuery;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Driver\DriverProfile;
use App\Services\PlatformQueryServices\DriverQueryService;
use Throwable;

class DriverQueryController extends Controller
{
    protected $queryService;

    public function __construct(DriverQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index()
    {
        $user = auth()->user();
        $this->authorize('viewAny', DriverProfile::class);
        try {
            $data = $this->queryService->getAllDrivers();

            return Response::Paginate($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function show($id)
    {
        $driver = DriverProfile::find($id);
        if (! $driver) {
            return Response::Error(null, 'employee not found', 404);
        }
        $this->authorize('view', $driver);
        try {
            $data = $this->queryService->getDriverById($id);
            if ($data['code'] === 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}
