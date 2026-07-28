<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean', 'show_header' => 'boolean', 'show_restaurant_info' => 'boolean', 'show_social_links' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    public function items()
    {
        return $this->hasMany(Item::class)->orderBy('sort_order');
    }

    public function theme()
    {
        return $this->hasOne(MenuTheme::class);
    }

    public function views()
    {
        return $this->hasMany(MenuView::class);
    }
}
