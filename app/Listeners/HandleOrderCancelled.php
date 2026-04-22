<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Models\Notification;

class HandleOrderCancelled
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(OrderCancelled $event)
    {
        $order = $event->order;
        $reason = $event->reason;

        // Determine refund amount based on stage
        $refundPercentage = match ($order->status) {
            'pending' => 100,
            'confirmed' => 100,
            'preparing' => 80,
            'ready_for_pickup' => 50,
            default => 0,
        };

        // Process refund
        if ($refundPercentage > 0) {
            $refundAmount = ($order->invoice->total * $refundPercentage) / 100;
            $this->processRefund($order, $refundAmount);
        }

        // Cancel delivery if exists
        if ($order->delivery && $order->delivery->delivery_status !== 'delivered') {
            $order->delivery->update(['delivery_status' => 'cancelled']);
        }

        // Notify all parties
        $this->notifyAllParties($order, $reason);
    }

    private function processRefund($order, $amount)
    {
        $order->payment->update([
            'payment_status' => 'refunded',
        ]);

        // Actual refund logic (call payment gateway)
        // PaymentService::refund($order->payment->transaction_reference, $amount);
    }

    private function notifyAllParties($order, $reason)
    {
        // Notify customer
        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.cancelled',
            'title' => 'Order cancelled',
            'message' => "Order #{$order->order_number} has been cancelled.",
        ]);

        // Notify kitchen
        Notification::create([
            'user_id' => $order->branch->manager->user_id,
            'type' => 'order.cancelled',
            'title' => "Order #{$order->order_number} cancelled",
            'message' => "Reason: {$reason}",
        ]);

        // Notify driver if already assigned
        if ($order->driver) {
            Notification::create([
                'user_id' => $order->driver->user_id,
                'type' => 'delivery.cancelled',
                'title' => 'Delivery cancelled',
                'message' => "Delivery #{$order->order_number} has been cancelled.",
            ]);
        }
    }
}
