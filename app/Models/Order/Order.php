<?php

namespace App\Models\Order;

use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\Customer\CustomerProfile;
use App\Models\Driver\DriverProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['order_number', 'customer_id', 'company_id', 'branch_id', 'delivery_address_id', 'driver_id', 'status', 'notes'];

    public function customer()
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function driver()
    {
        return $this->belongsTo(DriverProfile::class, 'driver_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
