<?php

namespace App\Listeners;

use App\Events\OrderPreparing;
use App\Models\Notification;

class HandleOrderPreparing
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
     * WORKFLOW: Employee marks preparing → Notify customer with countdown timer
     */
    public function handle(OrderPreparing $event): void
    {
        $order = $event->order;

        $estimatedPrepTime = $order->orderItems()
            ->with('menuItem')
            ->get()
            ->max(fn ($item) => $item->menuItem->preparation_time_minutes ?? 0);

        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.preparing',
            'title' => 'Your order is being prepared',
            'message' => "Estimated ready time: {$estimatedPrepTime} minutes",
        ]);

    }
}
