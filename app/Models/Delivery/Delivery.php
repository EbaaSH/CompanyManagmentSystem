<?php

namespace App\Models\Delivery;

use App\Models\Driver\DriverProfile;
use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'driver_id', 'delivery_status', 'assigned_at', 'accepted_at', 'picked_up_at', 'delivered_at', 'proof_image_url', 'delivery_notes'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function driver()
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }

    public function deliveryStatusHistories()
    {
        return $this->hasMany(DeliveryStatusHistory::class);
    }
}
