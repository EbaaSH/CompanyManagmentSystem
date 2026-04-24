<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public $driver,
        public float $latitude,
        public float $longitude
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('driver.location.'.$this->driver->id),
            new PrivateChannel('driver.'.$this->driver->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driver->id,
            'driver_user_id' => $this->driver->user_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'message' => 'Driver location updated.',
        ];
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }
}
