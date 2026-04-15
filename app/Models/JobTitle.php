<?php

namespace App\Models;

use App\Models\Employee\EmployeeProfile;
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
