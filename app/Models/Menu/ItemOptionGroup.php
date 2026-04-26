<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemOptionGroup extends Model
{
    use HasFactory, SoftDeletes;
    protected static function booted()
    {
        static::deleting(function ($group) {

            if ($group->isForceDeleting()) {
                $group->itemOptions()->withTrashed()->forceDelete();
            } else {
                $group->itemOptions()->each(function ($option) {
                    $option->delete();
                });
            }
        });

        static::restoring(function ($group) {
            $group->itemOptions()->withTrashed()->each(function ($option) {
                $option->restore();
            });
        });
    }
    protected $fillable = ['item_id', 'name', 'min_select', 'max_select', 'is_required'];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'item_id');
    }

    public function itemOptions()
    {
        return $this->hasMany(ItemOption::class, 'option_group_id');
    }
}
