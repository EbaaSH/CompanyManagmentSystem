<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'label', 'address_line', 'city', 'latitude', 'longitude', 'is_default'];

    public function customer()
    {
        return $this->belongsTo(CustomerProfile::class);
    }
}
