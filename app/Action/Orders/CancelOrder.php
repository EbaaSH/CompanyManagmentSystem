<?php

namespace App\Action\Orders;

use App\Events\OrderCancelled;
use App\Models\Order\Order;
use App\Models\Order\OrderStatusHistory;
use App\Services\Customer\OrderStateMachine;

class CancelOrder
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

    public function cancel($userId, $reason = null)
    {
        $stateMachine = $this->stateMachine();

        if (! $stateMachine->canTransition('cancelled', auth()->user()?->getRoleNames()->first() ?? 'system')) {
            throw new \Exception("Cannot cancel order in {$this->order->status} status");
        }

        $this->order->update(['status' => 'cancelled']);

        $this->recordStatusHistory($this->order->status, 'cancelled', $userId, $reason);

        event(new OrderCancelled($this->order, $reason));

        return $this->order;
    }
}
