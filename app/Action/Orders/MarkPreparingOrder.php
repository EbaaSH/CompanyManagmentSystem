<?php

namespace App\Action\Orders;

use App\Events\OrderPreparing;
use App\Models\Order\Order;
use App\Models\Order\OrderStatusHistory;
use App\Services\Customer\OrderStateMachine;

class MarkPreparingOrder
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

    public function markPreparing($userId)
    {
        $stateMachine = $this->stateMachine();

        if (! $stateMachine->canTransition('preparing', 'employee')) {
            throw new \Exception('Order cannot be marked as preparing');
        }

        $this->order->update(['status' => 'preparing']);
        $this->recordStatusHistory('confirmed', 'preparing', $userId);

        event(new OrderPreparing($this->order));

        return $this->order;
    }
}
