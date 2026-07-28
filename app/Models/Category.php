<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function menuPage()
    {
        return $this->belongsTo(MenuPage::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class)->orderBy('sort_order');
    }
}
