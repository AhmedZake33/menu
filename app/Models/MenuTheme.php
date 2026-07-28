<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuTheme extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['show_item_images' => 'boolean', 'show_descriptions' => 'boolean', 'show_prices' => 'boolean', 'show_category_images' => 'boolean', 'sticky_categories' => 'boolean', 'enable_search' => 'boolean', 'enable_category_filter' => 'boolean', 'enable_dark_mode' => 'boolean'];
    }

    public function menuPage()
    {
        return $this->belongsTo(MenuPage::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
