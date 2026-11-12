<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    public const STATUSES = ['pending' => 'جديد', 'confirmed' => 'مؤكد', 'completed' => 'حضر', 'cancelled' => 'ملغي'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reserved_at' => 'datetime', 'guests' => 'integer'];
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
