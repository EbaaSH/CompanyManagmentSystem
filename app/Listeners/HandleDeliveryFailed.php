<?php

namespace App\Listeners;

use App\Events\DeliveryFailed;
use App\Jobs\AssignDriverJob;
use App\Models\Notification;

class HandleDeliveryFailed
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle delivery failure
     * WORKFLOW: Delivery failed → Auto-retry up to 3 times → Contact customer
     */
    public function handle(DeliveryFailed $event)
    {
        $delivery = $event->delivery;
        $order = $delivery->order;
        $reason = $event->reason;

        // Check retry attempt count
        if ($delivery->retry_attempt < 3) {
            // Auto-retry with new driver
            $this->scheduleRetry($order, $delivery);
        } else {
            // Max retries exceeded - contact customer
            $this->notifyCustomerForResolution($order, $reason);
        }

        // Log failure for analytics
        \Log::warning("Delivery failed for order #{$order->order_number}: {$reason}. Attempt: {$delivery->retry_attempt}");
    }

    /**
     * Schedule automatic retry with next available driver
     */
    private function scheduleRetry($order, $delivery)
    {
        $retryTime = match ($delivery->retry_attempt) {
            1 => now()->addHours(2),
            2 => now()->addDay(),
            default => now()->addHours(4),
        };

        // Unassign current driver
        $delivery->update([
            'driver_id' => null,
            'delivery_status' => 'unassigned',
            'scheduled_retry_at' => $retryTime,
        ]);

        // Queue new assignment
        AssignDriverJob::dispatch($order)->delay($retryTime);

        // Notify customer: attempting redelivery
        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.retry_delivery',
            'title' => 'Retrying delivery...',
            'message' => "We're attempting to deliver your order again at {$retryTime->format('H:i')}.",
        ]);
    }

    /**
     * Notify customer when max retries exceeded
     */
    private function notifyCustomerForResolution($order, $reason)
    {
        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.delivery_issue',
            'title' => 'Delivery Issue',
            'message' => "We couldn't deliver your order after 3 attempts. Reason: {$reason}. Please contact support for options (refund, reschedule, pickup).",
        ]);

        // Notify support team
        Notification::create([
            'user_id' => 1, // Admin/Support user ID
            'type' => 'admin.order_issue',
            'title' => "Order {$order->order_number} - Delivery Failed",
            'message' => "Max delivery attempts reached. Reason: {$reason}",
        ]);
    }
}
