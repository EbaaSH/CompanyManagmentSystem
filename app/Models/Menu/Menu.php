<?php

namespace App\Models\Menu;

use App\Models\Company\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'name', 'description', 'is_active', 'start_time', 'end_time'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function menuCategories()
    {
        return $this->hasMany(MenuCategory::class);
    }
}
