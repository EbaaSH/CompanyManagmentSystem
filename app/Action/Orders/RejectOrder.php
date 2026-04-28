<?php

namespace App\Action\Orders;

use App\Events\OrderRejected;
use App\Models\Order\Order;
use App\Models\Order\OrderStatusHistory;
use App\Services\Customer\OrderStateMachine;

class RejectOrder
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

    public function reject($userId, $reason = null)
    {

        $this->order->update(['status' => 'rejected']);
        $this->recordStatusHistory($this->order->status, 'rejected', $userId, $reason);

        event(new OrderRejected($this->order, $reason));

        return $this->order;
    }
}
