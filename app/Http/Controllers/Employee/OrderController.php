<?php

namespace App\Http\Controllers\Employee;

use App\Action\Orders\ConfirmOrder;
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
    private $confirmOrder;
    private $markPreparing;

    private $markReady;

    private $rejectOrder;


    public function __construct(RejectOrder $rejectOrder)
    {
        $this->rejectOrder = $rejectOrder;
    }

    public function confirm($orderId)
    {
        try {
            $user = auth()->user();
            $order = Order::query()
                ->forUserViaPermission($user)
                ->find($orderId);
            if (!$order) {
                return Response::Error(null, 'order not found', 404);
            }
            $this->authorize('confirm', $order);

            if ($order->status !== 'pending') {
                return Response::Error([], 'Order must be in pending status');
            }
            if ($order->payment->payment_status !== 'paid') {
                return Response::Error([], 'Order must be paid to be confirmed');
            }

            DB::beginTransaction();

            $this->confirmOrder = new ConfirmOrder($order);
            $this->confirmOrder->confirm($user->id);

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

            if ($order->status !== 'pending') {
                return Response::Error([], 'Can only reject pending orders');
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
}
