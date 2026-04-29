<?php

namespace App\Listeners;

use App\Events\DeliveryFailed;
use App\Models\Notification;

class HandleDeliveryFailed
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(DeliveryFailed $event)
    {
        $delivery = $event->delivery;
        $order = $delivery->order;
        $reason = $event->reason;

        if ($delivery->retry_attempt < 3) {
            Notification::create([
                'user_id' => $order->customer->user_id,
                'type' => 'order.retry_delivery',
                'title' => 'Retrying delivery...',
                'message' => "We're attempting to deliver your order again at {$delivery->scheduled_retry_at->format('H:i')}.",
            ]);
        } else {
            $this->notifyCustomerForResolution($order, $reason);
        }
        \Log::warning("Delivery failed for order #{$order->order_number}: {$reason}. Attempt: {$delivery->retry_attempt}");
    }

    private function notifyCustomerForResolution($order, $reason)
    {
        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.delivery_issue',
            'title' => 'Delivery Issue',
            'message' => "We couldn't deliver your order after 3 attempts. Reason: {$reason}. Please contact support for options (refund, reschedule, pickup).",
        ]);

        Notification::create([
            'user_id' => 1,
            'type' => 'admin.order_issue',
            'title' => "Order {$order->order_number} - Delivery Failed",
            'message' => "Max delivery attempts reached. Reason: {$reason}",
        ]);
    }
}
