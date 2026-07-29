<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuOrderItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(MenuOrder::class, 'menu_order_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
