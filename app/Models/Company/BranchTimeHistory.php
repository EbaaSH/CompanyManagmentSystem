<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchTimeHistory extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'opening_time', 'closing_time', 'operation_date'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
