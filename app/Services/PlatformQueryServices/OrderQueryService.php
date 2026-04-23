<?php

namespace App\Services\PlatformQueryServices;

use App\Models\Order\Order;

class OrderQueryService
{
    public function getOrderById($orderId)
    {
        $user = auth()->user();
        $order = Order::query()
            ->forUserViaPermission($user)
            ->with(
                'deliveryAddress',
                'orderItems',
                'orderInvoice',
                'orderStatus',
                'statusHistories',
                'orderItems.menuItem',
                'orderItems.orderItemOptions'
            )
            ->find($orderId);
        if (! $order) {
            return [
                'data' => null,
                'message' => 'order not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $order,
            'message' => 'order found',
            'code' => 200,
        ];
    }

    public function getAllorders()
    {
        $user = auth()->user();
        $order = Order::query()
            ->forUserViaPermission($user)
            ->with(
                'deliveryAddress',
                'orderItems',
                'orderInvoice',
                'orderStatus',
                'statusHistories',
                'orderItems.menuItem',
                'orderItems.orderItemOptions'
            )
            ->paginate(10);

        return [
            'data' => $order,
            'message' => 'order found',
            'code' => 200,
        ];
    }
}
