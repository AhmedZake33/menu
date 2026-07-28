<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuView extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function menuPage()
    {
        return $this->belongsTo(MenuPage::class);
    }
}
