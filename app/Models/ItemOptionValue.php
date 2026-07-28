<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOptionValue extends Model
{
    protected $guarded = ['id', 'item_option_id'];

    protected function casts(): array
    {
        return ['additional_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function option()
    {
        return $this->belongsTo(ItemOption::class, 'item_option_id');
    }
}
