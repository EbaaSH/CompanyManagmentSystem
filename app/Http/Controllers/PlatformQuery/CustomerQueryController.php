<?php

namespace App\Http\Controllers\PlatformQuery;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Customer\CustomerProfile;
use App\Services\PlatformQueryServices\CustomerQueryService;
use Throwable;

class CustomerQueryController extends Controller
{
    protected $queryService;

    public function __construct(CustomerQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index()
    {
        $this->authorize('viewAny', CustomerProfile::class);
        try {
            $data = $this->queryService->getAllCustomers();

            return Response::Paginate($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function show($id)
    {
        $customer = CustomerProfile::find($id);
        if (! $customer) {
            return [
                'data' => null,
                'message' => 'customer not found',
                'code' => 404,
            ];
        }
        $this->authorize('view', $customer);
        try {
            $data = $this->queryService->getCustomerById($id);
            if ($data['code'] === 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}
