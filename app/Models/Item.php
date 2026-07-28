<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'old_price' => 'decimal:2', 'is_available' => 'boolean', 'is_active' => 'boolean', 'is_featured' => 'boolean', 'is_new' => 'boolean'];
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function menuPage()
    {
        return $this->belongsTo(MenuPage::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function options()
    {
        return $this->hasMany(ItemOption::class)->orderBy('sort_order');
    }
}
