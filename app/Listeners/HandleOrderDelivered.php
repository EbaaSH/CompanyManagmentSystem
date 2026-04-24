<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Events\PaymentProcessed;
use App\Models\Notification;

class HandleOrderDelivered
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle order delivered
     * WORKFLOW: Order delivered → Payment processed → Loyalty points awarded → Rating request
     */
    public function handle(OrderDelivered $event)
    {
        $order = $event->order;

        // Emit payment processed event (if not already done in Delivery model)
        if ($order->payment && $order->payment->payment_status === 'pending') {
            event(new PaymentProcessed($order->payment));
        }

        // Add loyalty points (1 point per $1)
        $loyaltyPoints = floor($order->orderInvoice->total);
        if ($order->customer && $order->customer->user) {
            $order->customer->increment('loyalty_points', $loyaltyPoints);
        }

        // Notify customer
        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.delivered',
            'title' => 'Order delivered!',
            'message' => "Thank you! Order #{$order->order_number} delivered. You earned {$loyaltyPoints} loyalty points.",
        ]);

        // Request rating after 5 minutes
        $this->scheduleRatingRequest($order);

        // Update driver to available
        if ($order->delivery && $order->delivery->driver) {
            $order->delivery->driver->update(['availability_status' => 'available']);
        }
    }

    /**
     * Schedule rating request job
     */
    private function scheduleRatingRequest($order)
    {
        // Dispatch job to ask for rating after 5 minutes
        // \App\Jobs\RequestOrderRatingJob::dispatch($order)->delay(now()->addMinutes(5));
    }
}
