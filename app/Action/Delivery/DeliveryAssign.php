<?php

namespace App\Action\Delivery;

use App\Events\DriverAssigned;
use App\Models\Delivery\Delivery;
use App\Models\Driver\DriverProfile;

class DeliveryAssign
{
    private $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public function assign(DriverProfile $driver, $userId = null)
    {
        if ($this->delivery->delivery_status !== 'unassigned') {
            throw new \Exception('Delivery cannot be assigned in current status');
        }

        if ($driver->availability_status !== 'available') {
            throw new \Exception('this driver is not available');
        }

        $this->delivery->update([
            'driver_id' => $driver->id,
            'delivery_status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $this->delivery->order->update([
            'driver_id' => $driver->id,
        ]);

        $this->delivery->recordStatusHistory('unassigned', 'assigned', $userId ?? 1);

        // Fire event
        event(new DriverAssigned($this->delivery));

        $driver->setAvailability('busy');

        return $this->delivery;
    }
}