<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderInvoice extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'subtotal', 'delivery_free', 'discount', 'tax', 'total'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
