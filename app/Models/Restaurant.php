<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restaurant extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ordering_enabled' => 'boolean',
            'tables_count' => 'integer',
            'expires_at' => 'datetime',
            'map_latitude' => 'decimal:7',
            'map_longitude' => 'decimal:7',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function googlePlaceId(): ?string
    {
        if ($placeId = trim((string) $this->google_place_id)) {
            return $placeId;
        }

        if (! $this->map_url) {
            return null;
        }

        $patterns = [
            '/(?:placeid|place_id|ftid|cid)=([^&\s]+)/i',
            '/!1s([\w:-]+)/',
            '%/maps/place/([\w-]+)%',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->map_url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function googleReviewUrl(): string
    {
        if ($placeId = $this->googlePlaceId()) {
            return 'https://search.google.com/local/writereview?placeid='.rawurlencode($placeId);
        }

        $query = trim(implode(' ', array_filter([$this->name, $this->address])));

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function menuPages()
    {
        return $this->hasMany(MenuPage::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function menuViews()
    {
        return $this->hasMany(MenuView::class);
    }

    public function orders()
    {
        return $this->hasMany(MenuOrder::class);
    }
}
