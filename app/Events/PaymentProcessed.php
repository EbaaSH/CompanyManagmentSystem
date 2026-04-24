<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Payment $payment)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payment.'.$this->payment->id),
            new PrivateChannel('order.'.$this->payment->order_id),
            new PrivateChannel('customer.'.$this->payment->order?->customer?->user_id),
            new PrivateChannel('branch.'.$this->payment->order?->branch_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'order_id' => $this->payment->order_id,
            'order_number' => $this->payment->order?->order_number,
            'customer_user_id' => $this->payment->order?->customer?->user_id,
            'branch_id' => $this->payment->order?->branch_id,
            'amount' => $this->payment->amount,
            'payment_method' => $this->payment->payment_method,
            'payment_status' => $this->payment->payment_status,
            'transaction_reference' => $this->payment->transaction_reference,
            'message' => 'Payment has been processed successfully.',
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.processed';
    }
}
