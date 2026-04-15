<?php

namespace App\Models\Driver;

use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'company_id', 'branch_id', 'vehicle_type', 'plate_number', 'availability_status', 'current_latitude', 'current_longitude', 'is_active'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
