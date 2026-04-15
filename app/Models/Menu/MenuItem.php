<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'description', 'image_url', 'price', 'is_available', 'preparation_time_minutes'];

    public function category()
    {
        return $this->belongsTo(MenuCategory::class);
    }

    public function itemOptionGroups()
    {
        return $this->hasMany(ItemOptionGroup::class);
    }
}
