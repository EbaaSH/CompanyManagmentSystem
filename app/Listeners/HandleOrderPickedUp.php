<?php

namespace App\Listeners;

use App\Events\OrderPickedUp;
use App\Models\Notification;

class HandleOrderPickedUp
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
    public function handle(OrderPickedUp $event): void
    {
        $order = $event->order;

        // Notify customer
        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.picked_up',
            'title' => 'On the way!',
            'message' => 'Your delivery is on the way. Click to track.',
        ]);

        // Send live tracking link
        // $order->customer->user->sendTrackingLink($order);
    }
}
