<?php

namespace App\Action\Orders;

use App\Events\OrderReady;
use App\Models\Order\Order;
use App\Models\Order\OrderStatusHistory;
use App\Services\Customer\OrderStateMachine;

class MarkReadyOrder
{
    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function stateMachine(): OrderStateMachine
    {
        return new OrderStateMachine($this->order);
    }

    /**
     * Record status history (audit trail)
     */
    public function recordStatusHistory($oldStatus, $newStatus, $userId, $reason = null)
    {
        return OrderStatusHistory::create([
            'order_id' => $this->order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_user_id' => $userId,
            'reason' => $reason ?? '',
        ]);
    }

    public function markReady($userId)
    {
        $stateMachine = $this->stateMachine();

        if (! $stateMachine->canTransition('ready_for_pickup', 'employee')) {
            throw new \Exception('Order cannot be marked as ready');
        }

        // Create delivery if doesn't exist
        if (! $this->order->delivery) {
            $this->order->delivery()->create([
                'delivery_status' => 'unassigned',
            ]);
            $this->order->refresh();
        }

        $this->order->update(['status' => 'ready_for_pickup']);
        $this->recordStatusHistory('preparing', 'ready_for_pickup', $userId);

        $this->order->orderStatus->update([
            'ready_at' => now(),
        ]);

        // FIRE EVENT: This triggers auto-driver assignment
        event(new OrderReady($this->order));

        return $this->order;
    }
}
