<?php

namespace App\Events;

use App\Models\Order\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.'.$this->order->id),
            new PrivateChannel('customer.'.$this->order->customer?->user_id),
            new PrivateChannel('branch.'.$this->order->branch_id),
            new PrivateChannel('driver.'.$this->order->driver?->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'customer_id' => $this->order->customer_id,
            'customer_user_id' => $this->order->customer?->user_id,
            'branch_id' => $this->order->branch_id,
            'driver_id' => $this->order->driver_id,
            'driver_user_id' => $this->order->driver?->user_id,
            'message' => "Order #{$this->order->order_number} status changed to {$this->order->status}.",
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.changed';
    }
}
