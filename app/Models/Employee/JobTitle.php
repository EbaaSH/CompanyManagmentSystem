<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model
{
    use HasFactory;

    protected $fillable = ['job_title'];

    public function employeeProfiles()
    {
        return $this->hasMany(EmployeeProfile::class, 'job_title_id');
    }
}
