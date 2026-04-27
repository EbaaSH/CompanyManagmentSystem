<?php

namespace App\Listeners;

use App\Events\OrderRejected;
use App\Models\Notification;

class HandleOrderRejected
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
    public function handle(OrderRejected $event): void
    {
        $order = $event->order;

        // 1. Cancel delivery if exists
        if ($order->delivery) {
            $order->delivery->update([
                'delivery_status' => 'failed',
            ]);
        }

        // 2. Refund if needed
        if ($order->payment && $order->payment->payment_status === 'paid') {
            $order->payment->update([
                'payment_status' => 'refunded',
            ]);
        }

        // 3. Create notification
        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.rejected',
            'title' => 'Order rejected',
            'message' => $event->reason,
        ]);
    }
}
