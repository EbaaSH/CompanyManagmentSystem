<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemOption extends Model
{
    use HasFactory;

    protected $fillable = ['option_group_id', 'name', 'extra_price', 'is_available'];

    public function optionGroup()
    {
        return $this->belongsTo(ItemOptionGroup::class, 'option_group_id');
    }
}
