<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = ['title', 'description', 'discount_type', 'discount_value', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return ['discount_value' => 'decimal:2', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function scopeCurrent(Builder $query): void
    {
        $now = now();
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'موقوف';
        }

        if ($this->starts_at?->isFuture()) {
            return 'مجدول';
        }

        return $this->ends_at?->isPast() ? 'منتهي' : 'ساري';
    }
}
