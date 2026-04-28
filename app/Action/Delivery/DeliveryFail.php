<?php

namespace App\Action\Delivery;

use App\Events\DeliveryFailed;
use App\Jobs\AssignDriverJob;
use App\Models\Delivery\Delivery;
use App\Models\Delivery\DeliveryStatusHistory;

class DeliveryFail
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

    public function fail($userId, $reason = null)
    {

        $this->delivery->increment('retry_attempt');

        // Max 3 retry attempts
        if ($this->delivery->retry_attempt >= 3) {
            $this->delivery->update(['delivery_status' => 'failed']);
            $this->delivery->order->update(['status' => 'failed_delivery']);
            $this->delivery->order->recordStatusHistory('picked_up', 'failed_delivery', $userId, "Delivery failed after {$this->delivery->retry_attempt} attempts. Reason: {$reason}");

            // Notify customer of options (refund, reschedule, pickup)
            event(new DeliveryFailed($this->delivery, $reason));

            return $this->delivery;
        }

        // Schedule retry
        $retryTime = match ($this->delivery->retry_attempt) {
            1 => now()->addMinutes(2),
            2 => now()->addMinutes(5),
            default => now()->addMinutes(10),
        };
        $driver = $this->delivery->driver;

        $this->delivery->update([
            'delivery_status' => 'unassigned',
            'driver_id' => null,
            'scheduled_retry_at' => $retryTime,
        ]);

        $this->recordStatusHistory('picked_up', 'unassigned', $userId, "Failed delivery - Retry {$this->delivery->retry_attempt}. Reason: {$reason}");

        // Fire event
        event(new DeliveryFailed($this->delivery, $reason));

        // Queue retry job for scheduled time
        AssignDriverJob::dispatch($this->delivery->order)->delay($retryTime);
        $driver->setAvailability('available');

        return $this->delivery;
    }
}
