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
        $order->update(['status' => 'cancelled']);
        // Process refund
        if ($refundPercentage > 0) {
            $refundAmount = ($order->orderInvoice->total * $refundPercentage) / 100;
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
        $payment = $order->payment;

        // No payment or not paid → nothing to refund
        if (!$payment || $payment->payment_status !== 'paid') {
            return;
        }

        try {
            $refundService = app(\App\Services\StripePayment\StripeService::class);

            $refund = $refundService->refund($payment, $amount);

            // ✅ Update DB ONLY after success
            $payment->update([
                'payment_status' => 'refunded',
                'refunded_amount' => $amount,
                'refunded_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Refund failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            // Optional: mark as refund_failed
            $payment->update([
                'payment_status' => 'failed',
            ]);
        }
    }

    private function notifyAllParties($order, $reason)
    {
        // Notify customer
        $customerUserId = $order->customer?->user_id;

        if ($customerUserId) {
            Notification::create([
                'user_id' => $customerUserId,
                'type' => 'order.cancelled',
                'title' => 'Order cancelled',
                'message' => "Order #{$order->order_number} has been cancelled.",
            ]);
        }

        // Notify branch manager (kitchen)
        $managerUserId = $order->branch->manager->id;

        if ($managerUserId) {
            Notification::create([
                'user_id' => $managerUserId,
                'type' => 'order.cancelled',
                'title' => "Order #{$order->order_number} cancelled",
                'message' => "Reason: {$reason}",
            ]);
        }
        // Notify driver
        $driverUserId = $order->driver?->user_id;
        if ($driverUserId) {
            Notification::create([
                'user_id' => $driverUserId,
                'type' => 'delivery.cancelled',
                'title' => 'Delivery cancelled',
                'message' => "Delivery #{$order->order_number} has been cancelled.",
            ]);
        }
    }
}
