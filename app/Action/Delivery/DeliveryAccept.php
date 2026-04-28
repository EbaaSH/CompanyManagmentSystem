<?php

namespace App\Action\Delivery;

use App\Events\DeliveryAccepted;
use App\Models\Delivery\Delivery;
use App\Models\Delivery\DeliveryStatusHistory;


class DeliveryAccept
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

    public function accept($userId)
    {
        $this->delivery->update([
            'delivery_status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $this->delivery->recordStatusHistory('assigned', 'accepted', $userId);

        // Fire event
        event(new DeliveryAccepted($this->delivery));

        return $this->delivery;
    }
}