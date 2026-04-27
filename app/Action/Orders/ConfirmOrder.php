<?php

namespace App\Action\Orders;

use App\Events\OrderConfirmed;
use App\Models\Order\Order;
use App\Models\Order\OrderStatusHistory;
use App\Services\Customer\OrderStateMachine;

class ConfirmOrder
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

    public function autoConfirm()
    {
        $userId = auth()->user()->id;
        $stateMachine = $this->stateMachine();

        if (! $stateMachine->canTransition('confirmed', 'system')) {
            throw new \Exception('Order cannot be auto-confirmed');
        }

        $this->order->update(['status' => 'confirmed']);
        $this->recordStatusHistory('pending', 'confirmed', $userId, 'Auto-confirmed by system');

        // Fire event
        event(new OrderConfirmed($this->order));

        return $this;
    }
}
