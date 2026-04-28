<?php

namespace App\Http\Controllers\PlatformQuery;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Delivery\Delivery;
use App\Services\PlatformQueryServices\DeliveryQueryService;
use Illuminate\Http\Request;
use Throwable;

class DeliveryQueryController extends Controller
{
    protected $queryService;

    public function __construct(DeliveryQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index()
    {
        try {
            $user = auth()->user();
            $this->authorize('viewAny', Delivery::class);
            $data = $this->queryService->getAllDelivery();

            return Response::Paginate($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function show($id)
    {
        $user = auth()->user();
        $delivery = Delivery::query()
            ->forUserViaPermission($user)
            ->with(
                'order.customer.user',
                'order.deliveryAddress',
                'order.orderItems.menuItem',
                'order.branch',
                'statusHistories',
            )
            ->find($id);
        if (!$delivery) {
            return Response::Error(null, 'delivery not found', 404);
        }
        $this->authorize('view', $delivery);
        try {
            $data = $this->queryService->getDeliveriesById($id);
            if ($data['code'] === 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}
