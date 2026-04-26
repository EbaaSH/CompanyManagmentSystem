<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['menu_id', 'name', 'sort_order', 'is_active'];

    protected static function booted()
    {
        // 🔻 DELETE CASCADE
        static::deleting(function ($category) {

            if ($category->isForceDeleting()) {
                $category->menuItems()->withTrashed()->forceDelete();
            } else {
                $category->menuItems()->each(function ($item) {
                    $item->delete();
                });
            }
        });

        // 🔄 RESTORE CASCADE
        static::restoring(function ($category) {
            $category->menuItems()->withTrashed()->each(function ($item) {
                $item->restore();
            });
        });
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'category_id');
    }
}
