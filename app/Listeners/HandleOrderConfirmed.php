<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Models\Notification;

class HandleOrderConfirmed
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderConfirmed $event): void
    {
        $order = $event->order;

        // Send to kitchen via push notification
        $kitchenStaff = $order->branch->employees()
            ->whereHas('user', fn ($q) => $q->whereHas('roles', fn ($q) => $q->whereIn('name', ['employee', 'branch_manager'])))
            ->get();

        foreach ($kitchenStaff as $staff) {
            Notification::create([
                'user_id' => $staff->user_id,
                'type' => 'order.confirmed',
                'title' => "New Order #{$order->order_number}",
                'message' => 'Items: '.$order->items->pluck('item_name_snapshot')->join(', '),
            ]);

            // Send push notification
            // $staff->user->sendPushNotification(...);
        }

        // Log to kitchen display system
        // broadcast(new OrderToKitchenDisplay($order));
    }
}
