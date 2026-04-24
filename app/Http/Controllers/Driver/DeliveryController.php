<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\AcceptDeliveryRequest;
use App\Http\Requests\Driver\DeliverOrderRequest;
use App\Http\Requests\Driver\FailDeliveryRequest;
use App\Http\Requests\Driver\PickupOrderRequest;
use App\Http\Requests\Driver\RejectDeliveryRequest;
use App\Http\Responses\Response;
use App\Models\Delivery\Delivery;
use App\Traits\UploadImage;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeliveryController extends Controller
{
    use UploadImage;

    /**
     * Get all assigned deliveries for driver
     */
    public function getMyDeliveries()
    {
        try {
            $driverId = auth()->user()->driverProfile->id;

            $deliveries = Delivery::where('driver_id', $driverId)
                ->with([
                    'order.customer.user',
                    'order.deliveryAddress',
                    'order.orderItems.menuItem',
                    'order.branch',
                    'statusHistories',
                ])
                ->whereIn('delivery_status', ['assigned', 'accepted', 'picked_up'])
                ->latest()
                ->paginate(15);

            return Response::Success($deliveries, 'My deliveries retrieved', 200);

        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Accept delivery assignment
     * Driver accepts the delivery order
     */
    public function accept(AcceptDeliveryRequest $request, $deliveryId)
    {
        try {
            $delivery = Delivery::find($deliveryId);
            if (! $delivery) {
                return Response::Error(null, 'delivery not found', 404);
            }
            $this->authorize('update', $delivery);

            if ($delivery->delivery_status !== 'assigned') {
                return Response::Error([], 'Delivery must be in assigned status');
            }

            if ($delivery->driver_id !== auth()->user()->driverProfile->id) {
                return Response::Error([], 'This delivery is not assigned to you');
            }

            DB::beginTransaction();

            $delivery->accept(auth()->id());

            DB::commit();

            return Response::Success(
                $delivery->load('order.orderItems', 'order.customer', 'order.branch'),
                'Delivery accepted. Head to branch for pickup',
                200
            );

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Reject delivery assignment
     * Driver rejects the delivery (triggers auto-reassignment)
     */
    public function reject(RejectDeliveryRequest $request, $deliveryId)
    {
        try {
            $delivery = Delivery::findOrFail($deliveryId);
            $this->authorize('update', $delivery);

            if ($delivery->delivery_status !== 'assigned') {
                return Response::Error([], 'Can only reject assigned deliveries');
            }

            if ($delivery->driver_id !== auth()->user()->driverProfile->id) {
                return Response::Error([], 'This delivery is not assigned to you');
            }

            DB::beginTransaction();

            $delivery->reject(auth()->id(), $request->reason);

            DB::commit();

            return Response::Success(
                $delivery->load('order.customer'),
                'Delivery rejected. Another driver will be assigned',
                200
            );

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Pickup order from branch
     * Driver picks up order from branch
     */
    public function pickup(PickupOrderRequest $request, $deliveryId)
    {
        try {
            $delivery = Delivery::findOrFail($deliveryId);
            if (! $delivery) {
                return Response::Error(null, 'delivery not found', 404);
            }
            $this->authorize('update', $delivery);

            if ($delivery->delivery_status !== 'accepted') {
                return Response::Error([], 'Delivery must be accepted first');
            }

            if ($delivery->driver_id !== auth()->user()->driverProfile->id) {
                return Response::Error([], 'This delivery is not assigned to you');
            }

            DB::beginTransaction();

            $delivery->pickUp(auth()->id());

            DB::commit();

            return Response::Success(
                $delivery->load('order.customer', 'order.deliveryAddress'),
                'Order picked up. Start delivery to customer',
                200
            );

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Deliver order to customer
     * Driver delivers order to customer and provides proof
     */
    public function deliver(DeliverOrderRequest $request, $deliveryId)
    {
        try {
            $delivery = Delivery::find($deliveryId);

            if (! $delivery) {
                return Response::Error(null, 'deliverey not found', 404);
            }

            $this->authorize('update', $delivery);

            if ($delivery->delivery_status !== 'picked_up') {
                return Response::Error([], 'Order must be picked up first');
            }

            if ($delivery->driver_id !== auth()->user()->driverProfile->id) {
                return Response::Error([], 'This delivery is not assigned to you');
            }

            DB::beginTransaction();

            $path = $this->uploadImage($request, "delivery/driver/{$delivery->driver_id}/proof", 'image');

            if (! $path['success']) {
                return Response::Error(null, 'the image not uploaded', 400);
            }

            $delivery->deliver(
                auth()->id(),
                $path['data'],
                $request->delivery_notes
            );

            DB::commit();

            return Response::Success(
                $delivery->load('order.customer'),
                'Order delivered successfully! Thank you for the delivery',
                200
            );

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Mark delivery as failed
     * Driver marks delivery as failed (triggers retry logic)
     */
    public function fail(FailDeliveryRequest $request, $deliveryId)
    {
        try {
            $delivery = Delivery::findOrFail($deliveryId);
            $this->authorize('update', $delivery);

            if ($delivery->delivery_status !== 'picked_up') {
                return Response::Error([], 'Can only fail deliveries that are picked up');
            }

            if ($delivery->driver_id !== auth()->user()->driverProfile->id) {
                return Response::Error([], 'This delivery is not assigned to you');
            }

            DB::beginTransaction();

            $delivery->fail(auth()->id(), $request->reason);

            DB::commit();

            $message = $delivery->retry_attempt >= 3
                ? 'Delivery marked as failed. Customer will be contacted for resolution'
                : "Delivery marked as failed. Retry {$delivery->retry_attempt} scheduled";

            return Response::Success(
                $delivery->load('order.customer'),
                $message,
                200
            );

        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    /**
     * Get delivery details
     */
    public function show($deliveryId)
    {
        try {
            $delivery = Delivery::with([
                'order.orderItems.menuItem',
                'order.customer.user',
                'order.deliveryAddress',
                'order.branch',
                'driver.user',
                'statusHistories',
            ])->findOrFail($deliveryId);

            $this->authorize('view', $delivery);

            return Response::Success($delivery, 'Delivery details retrieved', 200);

        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}
