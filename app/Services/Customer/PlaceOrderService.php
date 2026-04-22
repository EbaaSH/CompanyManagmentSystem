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
                        'option_group_name_snapshot' => $option->optionGroup->name,
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
            'delivery_fee' => 0,
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
            'data' => $order->load(
                'deliveryAddress',
                'orderItems',
                'orderInvoice',
                'orderStatus',
                'statusHistories',
                'orderItems.menuItem',
                'orderItems.orderItemOptions'
            ),
            'message' => 'order placed successfully',
            'code' => 201,
        ];
    }

    public function updateOrder($request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return [
                'data' => null,
                'message' => 'You cannot update the order',
                'code' => 400,
            ];
        }

        // Update order fields like delivery address and notes
        $order->update([
            'delivery_address_id' => $request->delivery_address_id,
            'notes' => $request->notes ?? '',
        ]);

        // Delete old order items
        $order->orderItems()->delete();

        // Add new order items
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
                        'option_group_name_snapshot' => $option->optionGroup->name,
                        'option_name_snapshot' => $option->name,
                        'extra_price' => $option->extra_price,
                    ]);

                    $lineTotal += $option->extra_price;
                }
            }

            $subtotal += $lineTotal;
        }

        // Update invoice
        $order->orderInvoice()->update([
            'subtotal' => $subtotal,
            'total' => $subtotal, // Assuming no tax, delivery fee, or discount for now
        ]);

        // Update status history
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'old_status' => $order->status,
            'new_status' => 'pending',  // Assuming the status is pending while updating
            'changed_by_user_id' => auth()->id(),
            'reason' => 'Order updated',
        ]);

        // Returning the updated order
        return [
            'data' => $order->load(
                'deliveryAddress',
                'orderItems',
                'orderInvoice',
                'orderStatus',
                'statusHistories',
                'orderItems.menuItem',
                'orderItems.orderItemOptions'
            ),
            'message' => 'Order updated successfully',
            'code' => 200,
        ];
    }
}
