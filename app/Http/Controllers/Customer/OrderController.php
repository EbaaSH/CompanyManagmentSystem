<?php

namespace App\Http\Controllers\Customer;

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

    /**
     * Update pending order
     * Only allowed in PENDING status
     */
    public function update(UpdateOrderRequest $request, $id)
    {
        $order = Order::find($id);
        if (! $order) {
            return Response::Error([], 'Order not found', 404);
        }
        $this->authorize('update', $order);
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
            $order = Order::findOrFail($id);
            $this->authorize('delete', $order);

            if (! in_array($order->status, ['pending', 'confirmed', 'preparing', 'ready_for_pickup'])) {
                return Response::Error([], "Cannot cancel order in {$order->status} status");
            }

            DB::beginTransaction();
            $user = auth()->user();
            $order->cancel($user->id, $request->reason ?? 'Customer cancelled');

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

    /**
     * Get my orders (customer's orders)
     */
    public function myOrders()
    {
        try {
            $customerId = auth()->user()->customerProfile->id;

            $orders = Order::where('customer_id', $customerId)
                ->with([
                    'orderItems.menuItem',
                    'delivery.driver.user',
                    'customer.user',
                    'branch',
                    'orderInvoice',
                    'payment',
                ])
                ->latest()
                ->paginate(15);

            return Response::Success($orders, 'My orders retrieved', 200);

        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Get order details with full information
     */
    public function show($id)
    {
        try {
            $order = Order::with([
                'orderItems.menuItem',
                'orderItems.orderItemOptions',
                'delivery.driver.user',
                'delivery.statusHistories',
                'customer.user',
                'branch',
                'orderInvoice',
                'payment',
                'statusHistories',
            ])->findOrFail($id);

            $this->authorize('view', $order);

            return Response::Success($order, 'Order details retrieved', 200);

        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Get active orders (in-progress deliveries)
     */
    public function activeOrders()
    {
        try {
            $customerId = auth()->user()->customerProfile->id;

            $orders = Order::where('customer_id', $customerId)
                ->whereIn('status', ['confirmed', 'preparing', 'ready_for_pickup', 'picked_up'])
                ->with([
                    'orderItems.menuItem',
                    'delivery.driver.user',
                    'branch',
                ])
                ->latest()
                ->get();

            return Response::Success($orders, 'Active orders retrieved', 200);

        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Track delivery (live tracking endpoint)
     */
    public function trackDelivery($id)
    {
        try {
            $order = Order::with('delivery.driver.user')->findOrFail($id);
            $this->authorize('view', $order);

            if (! $order->delivery) {
                return Response::Error([], 'Delivery not found');
            }

            $trackingData = [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'delivery_status' => $order->delivery->delivery_status,
                'driver' => $order->delivery->driver ? [
                    'name' => $order->delivery->driver->user->name,
                    'phone' => $order->delivery->driver->user->phone,
                    'vehicle' => $order->delivery->driver->vehicle_info ?? 'N/A',
                ] : null,
                'estimated_arrival' => $order->delivery->delivery_status === 'picked_up'
                    ? now()->addMinutes(20)->toIso8601String()
                    : null,
            ];

            return Response::Success($trackingData, 'Delivery tracking retrieved', 200);

        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}
