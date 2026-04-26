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
        try {
            $this->authorize('viewAny', CustomerProfile::class);
            $data = $this->queryService->getAllCustomers();
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
            $customer = CustomerProfile::query()
            ->forUserViaPermission($user)
            ->find($id);
            if (!$customer) {
                return Response::Error(null, 'customer not found', 404);
            }
            $this->authorize('view', $customer);
            $data = $this->queryService->getCustomerById($id);
            if ($data['code'] !== 200) {
                return Response::Error($data['data'], $data['message'], $data['code']);
            }

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}
