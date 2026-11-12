<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'discount' => 'decimal:2',
            'service' => 'decimal:2',
            'tax' => 'decimal:2',
            'paid_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function items()
    {
        return $this->hasMany(MenuOrderItem::class);
    }
}
