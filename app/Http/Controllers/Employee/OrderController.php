<?php

namespace App\Http\Controllers\Employee;

use App\Action\Orders\MarkPreparingOrder;
use App\Action\Orders\MarkReadyOrder;
use App\Action\Orders\RejectOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\RejectOrderRequest;
use App\Http\Responses\Response;
use App\Models\Order\Order;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller
{
    /**
     * Mark order as PREPARING
     * Employee starts preparing order in kitchen
     */
    private $markPreparing;

    private $markReady;

    private $rejectOrder;

    public function __construct(RejectOrder $rejectOrder)
    {
        $this->rejectOrder = $rejectOrder;
    }

    public function markPreparing($orderId)
    {
        try {
            $user = auth()->user();
            $order = Order::query()
                ->forUserViaPermission($user)
                ->find($orderId);
            if (!$order) {
                return Response::Error(null, 'order not found', 404);
            }
            $this->authorize('markPreparing', $order);

            if ($order->status !== 'confirmed') {
                return Response::Error([], 'Order must be in confirmed status');
            }

            DB::beginTransaction();

            $this->markPreparing = new MarkPreparingOrder($order);
            $this->markPreparing->markPreparing($user->id);

            DB::commit();

            return Response::Success(
                $order->load('delivery', 'orderItems', 'customer'),
                'Order marked as preparing',
                200
            );

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Mark order as READY_FOR_PICKUP
     * IMPORTANT: This triggers:
     * 1. Delivery auto-creation
     * 2. Async driver assignment job
     * 3. Customer notification
     */
    public function markReady($orderId)
    {
        try {
            $user = auth()->user();
            $order = Order::query()
                ->forUserViaPermission($user)
                ->find($orderId);
            if (!$order) {
                return Response::Error(null, 'order not found', 404);
            }
            $this->authorize('markReady', $order);

            if ($order->status !== 'preparing') {
                return Response::Error([], 'Order must be in preparing status');
            }

            DB::beginTransaction();
            $this->markReady = new MarkReadyOrder($order);
            $this->markReady->markReady($user->id);

            DB::commit();

            return Response::Success(
                $order->load('delivery', 'orderItems', 'customer'),
                'Order marked as ready. Driver assignment in progress...',
                200
            );

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Reject pending order
     * Employee/Admin rejects order before confirming
     */
    public function reject(RejectOrderRequest $request, $orderId)
    {
        try {
            $user = auth()->user();
            $order = Order::query()
                ->forUserViaPermission($user)
                ->find($orderId);
            if (!$order) {
                return Response::Error(null, 'order not found', 404);
            }
            $this->authorize('reject', $order);

            if ($order->status !== 'pending' && $order->status !== 'confirmed') {
                return Response::Error([], 'Can only reject pending or cofirmed orders');
            }

            DB::beginTransaction();

            $this->rejectOrder = new RejectOrder($order);
            $this->rejectOrder->reject($user->id, $request->reason);

            DB::commit();

            return Response::Success(
                $order->load('delivery', 'orderItems', 'customer'),
                'Order rejected successfully',
                200
            );

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Get all orders for kitchen (by branch)
     */
    // public function getKitchenOrders()
    // {
    //     try {
    //         $this->authorize('viewAny', Order::class);

    //         $branchId = auth()->user()->employeeProfile->branch_id;

    //         $orders = Order::where('branch_id', $branchId)
    //             ->whereIn('status', ['confirmed', 'preparing', 'ready_for_pickup'])
    //             ->with(['orderItems', 'customer', 'delivery'])
    //             ->latest()
    //             ->paginate(15);

    //         return Response::Success($orders, 'Kitchen orders retrieved', 200);

    //     } catch (Throwable $th) {
    //         return Response::Error([], $th->getMessage());
    //     }
    // }

    /**
     * Get specific order details
     */
    // public function show($orderId)
    // {
    //     try {
    //         $order = Order::find($orderId);
    //         if (! $order) {
    //             return Response::Error(null, 'order not found', 404);
    //         }

    //         $this->authorize('view', $order);

    //         $order = Order::with([
    //             'orderItems.menuItem',
    //             'orderItems.orderItemOptions',
    //             'customer.user',
    //             'delivery.driver.user',
    //             'delivery.statusHistories',
    //             'statusHistories',
    //             'orderInvoice',
    //             'payment',
    //         ])->findOrFail($orderId);

    //         return Response::Success($order, 'Order details retrieved', 200);

    //     } catch (Throwable $th) {
    //         return Response::Error([], $th->getMessage());
    //     }
    // }
}
