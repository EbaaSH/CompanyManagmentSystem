<?php

namespace App\Listeners;

use App\Events\OrderReady;
use App\Jobs\AssignDriverJob;
use App\Models\Notification;

class HandleOrderReady
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
     * WORKFLOW: Order marked ready → Delivery auto-created → Driver assignment job queued
     */
    public function handle(OrderReady $event): void
    {
        $order = $event->order;

        // Create delivery if not exists
        if (! $order->delivery) {
            $order->delivery()->create([
                'delivery_status' => 'unassigned',
            ]);
        }

        // Queue driver assignment job (async)
        AssignDriverJob::dispatch($order)->delay(now()->addSeconds(5));

        // Notify customer: "Your order is ready!"
        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.ready',
            'title' => 'Your order is ready!',
            'message' => 'Driver will be assigned shortly. Track your delivery soon!',
        ]);

        // Update customer session (WebSocket)
        // broadcast(new OrderStatusChanged($order));
    }
}
