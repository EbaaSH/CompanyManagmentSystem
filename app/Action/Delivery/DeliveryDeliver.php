<?php

namespace App\Action\Delivery;

use App\Events\OrderDelivered;
use App\Events\PaymentProcessed;
use App\Models\Delivery\Delivery;
use App\Models\Delivery\DeliveryStatusHistory;
use App\Models\Payment;

class DeliveryDeliver
{
    private $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public function recordStatusHistory($oldStatus, $newStatus, $userId, $reason = null)
    {
        return DeliveryStatusHistory::create([
            'delivery_id' => $this->delivery->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_user_id' => $userId,
            'reason' => $reason ?? '',
        ]);
    }

    private function processPayment()
    {
        $payment = $this->delivery->order->payment ?? Payment::where('order_id', $this->delivery->order_id)->first();

        if ($payment && $payment->payment_status === 'pending') {
            $payment->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            // Fire event
            event(new PaymentProcessed($payment));
        }
    }

    public function deliver($userId, $proofImageUrl = null, $notes = null)
    {
        $this->delivery->update([
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
            'proof_image_url' => $proofImageUrl,
            'delivery_notes' => $notes,
        ]);

        $this->recordStatusHistory('picked_up', 'delivered', $userId);

        // Update order status
        $this->delivery->order->update(['status' => 'delivered']);
        $this->delivery->order->recordStatusHistory('picked_up', 'delivered', $userId);
        $this->delivery->order->orderStatus->update(['delivered_at' => now()]);
        // Process payment
        $this->processPayment();

        // Fire event
        event(new OrderDelivered($this->delivery->order));
        $this->delivery->driver->setAvailability('available');

        return $this->delivery;
    }
}
