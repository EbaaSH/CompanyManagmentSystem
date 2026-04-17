<?php

namespace App\Models\Company;

use App\Models\Driver\DriverProfile;
use App\Models\Employee\EmployeeProfile;
use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'code', 'address', 'city', 'latitude', 'longitude', 'phone', 'is_active'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branchTimeHistories()
    {
        return $this->hasMany(
            BranchTimeHistory::class,
            'branch_id',
        );
    }

    public function employees()
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function drivers()
    {
        return $this->hasMany(DriverProfile::class);
    }
}
