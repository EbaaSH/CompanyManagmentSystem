<?php

namespace App\Listeners;

use App\Events\OrderRejected;
use App\Models\Notification;

class HandleOrderRejected
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
    public function handle(OrderRejected $event): void
    {
        $order = $event->order;

        // // 1. Cancel delivery if exists
        // if ($order->delivery) {
        //     $order->delivery->update([
        //         'delivery_status' => 'failed',
        //     ]);
        // }
        $payment = $order->payment;
        // 2. Refund if needed
        if ($order->payment && $order->payment->payment_status === 'paid') {
            try {


                if (!$payment || $payment->payment_status !== 'paid') {
                    return;
                }
                $amount = $order->orderInvoice->total;
                $refundService = app(\App\Services\StripePayment\StripeService::class);

                $refund = $refundService->refund($payment, $amount);

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

                $payment->update([
                    'payment_status' => 'failed',
                ]);
            }
        }

        $payment->update([
            'payment_status' => 'failed',
        ]);

        Notification::create([
            'user_id' => $order->customer->user_id,
            'type' => 'order.rejected',
            'title' => 'Order rejected',
            'message' => $event->reason,
        ]);
    }
}
