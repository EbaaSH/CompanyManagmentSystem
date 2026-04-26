<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['category_id', 'name', 'description', 'image_url', 'price', 'is_available', 'preparation_time_minutes'];

    protected static function booted()
    {
        static::deleting(function ($item) {

            if ($item->isForceDeleting()) {
                $item->itemOptionGroups()->withTrashed()->forceDelete();
            } else {
                $item->itemOptionGroups()->each(function ($group) {
                    $group->delete();
                });
            }
        });

        static::restoring(function ($item) {
            $item->itemOptionGroups()->withTrashed()->each(function ($group) {
                $group->restore();
            });
        });
    }
    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function itemOptionGroups()
    {
        return $this->hasMany(ItemOptionGroup::class, 'item_id');
    }
}
