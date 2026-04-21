<?php

namespace App\Services\Customer;

use App\Models\Menu\ItemOption;
use App\Models\Menu\MenuItem;
use App\Models\Order\Order;
use App\Models\Order\OrderInvoice;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderItemOption;
use App\Models\Order\OrderStatus;
use App\Models\Order\OrderStatusHistory;

class PlaceOrderService
{
    public function placeOrder($request)
    {

        $order = Order::create([
            'order_number' => uniqid('ORD-'),
            'customer_id' => auth()->user()->customerProfile->id,
            'company_id' => $request->company_id,
            'branch_id' => $request->branch_id,
            'delivery_address_id' => $request->delivery_address_id,
            'driver_id' => null,
            'status' => 'pending',
            'notes' => $request->notes ?? '',
        ]);

        $subtotal = 0;

        foreach ($request->items as $itemData) {

            $menuItem = MenuItem::findOrFail($itemData['menu_item_id']);

            $lineTotal = $menuItem->price * $itemData['quantity'];

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'item_name_snapshot' => $menuItem->name,
                'item_price_snapshot' => $menuItem->price,
                'quantity' => $itemData['quantity'],
                'notes' => $itemData['notes'] ?? '',
                'line_total' => $lineTotal,
            ]);

            // options
            if (! empty($itemData['options'])) {
                foreach ($itemData['options'] as $opt) {

                    $option = ItemOption::findOrFail($opt['option_id']);

                    OrderItemOption::create([
                        'order_item_id' => $orderItem->id,
                        'option_group_name_snapshot' => $option->group->name,
                        'option_name_snapshot' => $option->name,
                        'extra_price' => $option->extra_price,
                    ]);

                    $lineTotal += $option->extra_price;
                }
            }

            $subtotal += $lineTotal;
        }

        // invoice
        OrderInvoice::create([
            'order_id' => $order->id,
            'subtotal' => $subtotal,
            'delivery_free' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
        ]);

        // status history
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'old_status' => 'none',
            'new_status' => 'pending',
            'changed_by_user_id' => auth()->id(),
            'reason' => 'Order created',
        ]);

        // order Status
        OrderStatus::create([
            'order_id' => $order->id,
            'placed_at' => now(),
        ]);

        return [
            'data' => $order->load('deliveryAddress',
                'orderItems',
                'orderInvoice',
                'orderStatus',
                'statusHistories',
                'orderItems.menuItem',
                'orderItems.orderItemOptions'
            ),
            'message' => 'order placed successfully',
            'code' => 200,
        ];
    }

    public function updateOrder($request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return [
                'data' => null,
                'message' => 'you can not update the order',
                'code' => 400,
            ];
        }

        $order->update([
            'delivery_address_id' => $request->delivery_address_id,
            'notes' => $request->notes ?? '',
        ]);

        // delete old items
        $order->items()->delete();
        $order = Order::create([
            'order_number' => uniqid('ORD-'),
            'customer_id' => auth()->user()->customerProfile->id,
            'company_id' => $request->company_id,
            'branch_id' => $request->branch_id,
            'delivery_address_id' => $request->delivery_address_id,
            'driver_id' => null,
            'status' => 'pending',
            'notes' => $request->notes ?? '',
        ]);

        $subtotal = 0;

        foreach ($request->items as $itemData) {

            $menuItem = MenuItem::findOrFail($itemData['menu_item_id']);

            $lineTotal = $menuItem->price * $itemData['quantity'];

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'item_name_snapshot' => $menuItem->name,
                'item_price_snapshot' => $menuItem->price,
                'quantity' => $itemData['quantity'],
                'notes' => $itemData['notes'] ?? '',
                'line_total' => $lineTotal,
            ]);

            // options
            if (! empty($itemData['options'])) {
                foreach ($itemData['options'] as $opt) {

                    $option = ItemOption::findOrFail($opt['option_id']);

                    OrderItemOption::create([
                        'order_item_id' => $orderItem->id,
                        'option_group_name_snapshot' => $option->group->name,
                        'option_name_snapshot' => $option->name,
                        'extra_price' => $option->extra_price,
                    ]);

                    $lineTotal += $option->extra_price;
                }
            }

            $subtotal += $lineTotal;
        }

        // invoice
        OrderInvoice::create([
            'order_id' => $order->id,
            'subtotal' => $subtotal,
            'delivery_free' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
        ]);

        // status history
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'old_status' => 'none',
            'new_status' => 'pending',
            'changed_by_user_id' => auth()->id(),
            'reason' => 'Order created',
        ]);

        // order Status
        OrderStatus::create([
            'order_id' => $order->id,
            'placed_at' => now(),
        ]);

    }
}
