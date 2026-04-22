<?php

namespace App\Events;

use App\Models\Order\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): Channel
    {
        return new Channel("order.{$this->order->id}");
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }
}
