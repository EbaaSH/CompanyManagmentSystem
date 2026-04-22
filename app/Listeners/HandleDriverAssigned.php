<?php

namespace App\Listeners;

use App\Events\DriverAssigned;
use App\Models\Notification;

class HandleDriverAssigned
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
     * WORKFLOW: Driver assigned → Notify driver with order details
     */
    public function handle(DriverAssigned $event): void
    {
        $delivery = $event->delivery;
        $order = $delivery->order;
        $driver = $delivery->driver;

        // Notify driver
        Notification::create([
            'user_id' => $driver->user_id,
            'type' => 'delivery.assigned',
            'title' => "New Delivery: Order #{$order->order_number}",
            'message' => "Pickup from {$order->branch->name}. Customer: {$order->customer->user->name}",
        ]);

        // Update customer
        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'delivery.driver_assigned',
            'title' => 'Driver assigned!',
            'message' => 'A driver has been assigned. Get ready for delivery!',
        ]);
    }
}
