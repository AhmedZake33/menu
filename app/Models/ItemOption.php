<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOption extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function values()
    {
        return $this->hasMany(ItemOptionValue::class)->orderBy('sort_order');
    }
}
