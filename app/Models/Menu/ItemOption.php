<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemOption extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = ['option_group_id', 'name', 'extra_price', 'is_available'];

    public function optionGroup()
    {
        return $this->belongsTo(ItemOptionGroup::class, 'option_group_id');
    }
}
