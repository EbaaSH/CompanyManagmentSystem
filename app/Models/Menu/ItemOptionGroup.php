<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemOptionGroup extends Model
{
    use HasFactory;

    protected $fillable = ['item_id', 'name', 'min_select', 'max_select', 'is_required'];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function itemOptions()
    {
        return $this->hasMany(ItemOption::class);
    }
}
