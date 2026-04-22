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

        // Auto-confirm if all validations pass
        try {
            $order->autoConfirm();
            event(new OrderConfirmed($order));
        } catch (\Exception $e) {
            // Manual confirmation needed
            $this->notifyAdminForManualConfirmation($order);
        }
    }

    private function notifyAdminForManualConfirmation($order)
    {
        // Send notification to branch manager
        Notification::create([
            'user_id' => $order->branch->manager->user_id,
            'type' => 'order.needs_confirmation',
            'title' => 'Order needs manual confirmation',
            'message' => "Order #{$order->order_number} needs manual review",
        ]);
    }
}
