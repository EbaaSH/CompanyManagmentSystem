<?php

namespace App\Http\Controllers\Customer;

use App\Action\Orders\CancelOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CancelOrderRequest;
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

    protected $cancelOrder;

    public function __construct(PlaceOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Create/Place new order
     * WORKFLOW: Validate → Create → Auto-confirm → Notify kitchen
     */
    public function store(CreateOrderRequest $request)
    {
        try {
            $this->authorize('create', Order::class);
            DB::beginTransaction();
            $data = $this->orderService->placeOrder($request);
            if ($data['code'] !== 201) {
                DB::rollBack();

                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Update pending order
     * Only allowed in PENDING status
     */
    public function update(UpdateOrderRequest $request, $id)
    {
        try {
            $order = Order::find($id);
            if (! $order) {
                return Response::Error([], 'Order not found', 404);
            }
            $this->authorize('update', $order);
            DB::beginTransaction();
            $data = $this->orderService->updateOrder($request, $id);
            if ($data['code'] !== 200) {
                DB::rollBack();

                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Cancel order
     * Refund logic based on order status
     * PENDING: 100% refund
     * CONFIRMED: 100% refund
     * PREPARING: 80% refund
     * READY_FOR_PICKUP: 50% refund
     * PICKED_UP+: No refund
     */
    public function cancel(CancelOrderRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $order = Order::query()
                ->forUserViaPermission($user)
                ->find($id);
            if (! $order) {
                return Response::Error([], 'Order not found', 404);
            }
            $this->authorize('cancel', $order);
            $status = trim((string) $order->status);

            $cancelableStatuses = [
                'pending',
                'confirmed',
                'preparing',
                'ready_for_pickup',
            ];

            if (! in_array($status, $cancelableStatuses)) {
                return Response::Error([], "Cannot cancel order in {$status} status");
            }

            DB::beginTransaction();
            $this->cancelOrder = new CancelOrder($order);
            $this->cancelOrder->cancel($user->id, $request->reason ?? 'Customer cancelled');

            DB::commit();

            return Response::Success(
                $order->load('delivery', 'payment'),
                'Order cancelled successfully. Refund will be processed',
                200
            );

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }
}
