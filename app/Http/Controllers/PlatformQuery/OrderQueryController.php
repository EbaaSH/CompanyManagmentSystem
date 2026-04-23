<?php

namespace App\Http\Controllers\PlatformQuery;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Order\Order;
use App\Services\PlatformQueryServices\OrderQueryService;
use Throwable;

class OrderQueryController extends Controller
{
    protected $queryService;

    public function __construct(OrderQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index()
    {
        $user = auth()->user();
        $this->authorize('viewAny', Order::class);
        try {
            $data = $this->queryService->getAllorders();

            return Response::Paginate($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function show($id)
    {
        $order = Order::find($id);
        if (! $order) {
            return Response::Error(null, 'order not found', 404);
        }
        $this->authorize('view', $order);
        try {
            $data = $this->queryService->getOrderById($id);
            if ($data['code'] === 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}
