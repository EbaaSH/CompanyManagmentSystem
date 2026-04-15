<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'placed_at', 'confirmed_at', 'ready_at', 'picked_up_at', 'delivered_at', 'cancelled_at'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
