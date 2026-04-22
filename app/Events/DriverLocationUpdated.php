<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated
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

    public function broadcastOn(): Channel
    {
        return new Channel("driver.{$this->driver->id}");
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }
}
