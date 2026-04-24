<?php

namespace App\Events;

use App\Models\Delivery\Delivery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Delivery $delivery)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('delivery.'.$this->delivery->id),
            new PrivateChannel('driver.'.$this->delivery->driver?->user_id),
            new PrivateChannel('customer.'.$this->delivery->order?->customer?->user_id),
            new PrivateChannel('branch.'.$this->delivery->order?->branch_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'delivery_id' => $this->delivery->id,
            'order_id' => $this->delivery->order_id,
            'order_number' => $this->delivery->order?->order_number,
            'driver_id' => $this->delivery->driver_id,
            'driver_name' => $this->delivery->driver?->user?->name,
            'delivery_status' => $this->delivery->delivery_status,
            'message' => 'Delivery has been accepted by the driver.',
        ];
    }

    public function broadcastAs(): string
    {
        return 'delivery.accepted';
    }
}
