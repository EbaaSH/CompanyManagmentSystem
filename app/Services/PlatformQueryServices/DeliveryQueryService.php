<?php

namespace App\Services\PlatformQueryServices;

use App\Models\Delivery\Delivery;

class DeliveryQueryService
{
    public function getDeliveriesById($deliveryId)
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
            ->find($deliveryId);
        if (!$delivery) {
            return [
                'data' => null,
                'message' => 'delivery not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $delivery,
            'message' => 'delivery retrevied successfully',
            'code' => 200,
        ];

    }

    public function getAllDelivery()
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
            ->paginate(10);


        return [
            'data' => $delivery,
            'message' => 'delivery retrevied successfully',
            'code' => 200,
        ];
    }
}