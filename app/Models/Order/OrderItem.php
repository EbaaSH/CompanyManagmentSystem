<?php

namespace App\Models\Order;

use App\Models\Menu\MenuItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'menu_item_id', 'item_name_snapshot', 'item_price_snapshot', 'quantity', 'notes', 'line_total'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function orderItemOptions()
    {
        return $this->hasMany(OrderItemOption::class);
    }
}
