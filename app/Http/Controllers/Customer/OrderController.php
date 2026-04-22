<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateOrderRequest;
use App\Http\Requests\Customer\UpdateOrderRequest;
use App\Http\Responses\Response;
use App\Models\Order\Order;
use App\Services\Customer\PlaceOrderService;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(PlaceOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(CreateOrderRequest $request)
    {
        $this->authorize('create', Order::class);
        DB::beginTransaction();
        try {
            $data = $this->orderService->placeOrder($request);

            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    public function update(UpdateOrderRequest $request, $id)
    {
        $Order = Order::find($id);
        if (! $Order) {
            return [
                'data' => null,
                'message' => 'customer not found',
                'code' => 404,
            ];
        }
        $this->authorize('update', $Order);
        DB::beginTransaction();
        try {
            $data = $this->orderService->updateOrder($request, $id);

            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }
}
