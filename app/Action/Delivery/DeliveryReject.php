<?php

namespace App\Action\Delivery;

use App\Jobs\AssignDriverJob;
use App\Models\Delivery\Delivery;
use App\Models\Delivery\DeliveryStatusHistory;

class DeliveryReject
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

    public function reject($userId, $reason = null)
    {
        $driver = $this->delivery->driver;

        $this->delivery->update([
            'delivery_status' => 'rejected',
            'driver_id' => null, // Clear driver
        ]);

        $this->recordStatusHistory('assigned', 'rejected', $userId, $reason);

        // Immediately trigger re-assignment
        AssignDriverJob::dispatch($this->delivery->order);
        $driver->setAvailability('available');

        return $this->delivery;
    }
}
