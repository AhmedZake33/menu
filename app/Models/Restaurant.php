<?php

namespace App\Models;

use App\Services\GooglePlaceService;
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

        return app(GooglePlaceService::class)->extract($this->map_url);
    }

    public function hasGoogleReviewLink(): bool
    {
        return $this->googlePlaceId() !== null;
    }

    public function googleReviewUrl(): ?string
    {
        if (! $placeId = $this->googlePlaceId()) {
            return null;
        }

        return 'https://search.google.com/local/writereview?placeid='.rawurlencode($placeId);
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
