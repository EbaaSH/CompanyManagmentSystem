<?php

namespace App\Models;

use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'payment_method', 'gateway', 'payment_intent_id', 'payment_status', 'transaction_reference', 'amount', 'paid_at', 'gateway_payment_id', 'gateway_client_secret', 'paid_at', 'refunded_amount', 'refunded_at', 'currency'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
