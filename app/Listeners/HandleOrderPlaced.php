<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Events\OrderPlaced;
use App\Models\Notification;

class HandleOrderPlaced
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
    public function handle(OrderPlaced $event)
    {
        $order = $event->order;

        Notification::create([
            'user_id' => $order->branch->manager->id,
            'type' => 'order.needs_confirmation',
            'title' => 'Order needs manual confirmation',
            'message' => "Order #{$order->order_number} needs manual review",
        ]);
    }
}
