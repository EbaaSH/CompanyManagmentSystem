<?php

namespace App\Listeners;

use App\Events\OrderReady;
use App\Events\OrderStatusChanged;
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

    public function handle(OrderReady $event): void
    {
        $order = $event->order;

        if (! $order->delivery) {
            $order->delivery()->create([
                'delivery_status' => 'unassigned',
            ]);
        }

        AssignDriverJob::dispatch($order)->delay(now()->addSeconds(5));

        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.ready',
            'title' => 'Your order is ready!',
            'message' => 'Driver will be assigned shortly. Track your delivery soon!',
        ]);

    }
}
