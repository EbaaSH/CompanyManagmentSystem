<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['shift_name', 'start_time', 'end_time'];

    public function employeeProfiles()
    {
        return $this->hasMany(EmployeeProfile::class, 'shift_id');
    }
}
