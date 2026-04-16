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

    public function weekDays()
    {

        return $this->belongsToMany(
            WeekDay::class,
            'branch_week_days', // ✅ correct table
            'branch_time_history_id',
            'week_day_id'
        );
    }

    public function branchWeekDays()
    {
        return $this->hasMany(BranchWeekDay::class);
    }
}
