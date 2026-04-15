<?php

namespace App\Models\Company;

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
        return $this->hasMany(BranchTimeHistory::class);
    }
}
