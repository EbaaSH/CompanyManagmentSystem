<?php

namespace App\Action\Delivery;

use App\Events\OrderPickedUp;
use App\Models\Delivery\Delivery;
use App\Models\Delivery\DeliveryStatusHistory;

class DeliveryPickup
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

    public function pickUp($userId)
    {

        $this->delivery->update([
            'delivery_status' => 'picked_up',
            'picked_up_at' => now(),
        ]);

        $this->recordStatusHistory('accepted', 'picked_up', $userId);
        $this->delivery->order->orderStatus->update(['picked_up_at' => now()]);
        $this->delivery->order->update(['status' => 'picked_up']);
        // Fire event
        event(new OrderPickedUp($this->delivery->order));

        return $this->delivery;
    }
}
