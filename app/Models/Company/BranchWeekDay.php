<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;

class BranchWeekDay extends Model
{
    protected $fillable = [
        'branch_time_history_id',
        'week_day_id',
    ];
}
