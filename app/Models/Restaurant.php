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
            'modules' => 'array',
            'settings' => 'array',
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

    /**
     * Whether this client has a module. Anything not listed in config/modules.php is
     * off. "orders" lives in the ordering_enabled column, which the public menu
     * already reads, so there is a single source of truth for it.
     */
    public function hasModule(string $key): bool
    {
        $definition = config("modules.list.$key");

        if (! $definition) {
            return false;
        }

        if ($key === 'orders') {
            return (bool) $this->ordering_enabled;
        }

        return (bool) (($this->modules ?? [])[$key] ?? $definition['default']);
    }

    /** @return array<string, bool> */
    public function moduleStates(): array
    {
        return collect(config('modules.list'))->map(fn ($definition, $key) => $this->hasModule($key))->all();
    }

    /** Dashboard colour for this client; falls back to the system default. */
    public function brandColor(): string
    {
        $color = data_get($this->settings, 'brand_color');

        return is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color) ? strtolower($color) : config('modules.brand.default');
    }

    /** A limit of 0 (or none saved) means unlimited. */
    public function limit(string $key): int
    {
        return max(0, (int) data_get($this->settings, "limits.$key", 0));
    }

    public function reachedLimit(string $key, int $current): bool
    {
        return $this->limit($key) > 0 && $current >= $this->limit($key);
    }

    /** @return array{tax_percent: float, service_percent: float, allow_discount: bool, payment_methods: list<string>, receipt_footer: ?string} */
    public function cashierSettings(): array
    {
        $saved = (array) data_get($this->settings, 'cashier', []);
        $merged = [...config('modules.cashier_defaults'), ...$saved];
        $methods = array_values(array_intersect(array_keys(config('modules.payment_methods')), (array) $merged['payment_methods']));

        return [
            'tax_percent' => (float) $merged['tax_percent'],
            'service_percent' => (float) $merged['service_percent'],
            'allow_discount' => (bool) $merged['allow_discount'],
            'payment_methods' => $methods ?: ['cash'],
            'receipt_footer' => $merged['receipt_footer'],
        ];
    }

    /** @return array<string, string> key => label, only the methods this client accepts */
    public function paymentMethods(): array
    {
        return array_intersect_key(config('modules.payment_methods'), array_flip($this->cashierSettings()['payment_methods']));
    }

    public function saveCashierSettings(array $values): void
    {
        $settings = $this->settings ?? [];
        $settings['cashier'] = [...($settings['cashier'] ?? []), ...$values];
        $this->update(['settings' => $settings]);
    }

    /**
     * Service is charged on the discounted subtotal, tax on subtotal + service.
     *
     * @return array{subtotal: float, discount: float, service: float, tax: float, total: float}
     */
    public function cashierTotals(float $subtotal, float $discount = 0): array
    {
        $cashier = $this->cashierSettings();
        $net = max(0, $subtotal - $discount);
        $service = round($net * $cashier['service_percent'] / 100, 2);
        $tax = round(($net + $service) * $cashier['tax_percent'] / 100, 2);

        return ['subtotal' => $subtotal, 'discount' => $discount, 'service' => $service, 'tax' => $tax, 'total' => round($net + $service + $tax, 2)];
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
        return $this->googleReviewUrl() !== null;
    }

    public function googleReviewUrl(): ?string
    {
        $places = app(GooglePlaceService::class);

        if (! $identifier = $this->googlePlaceId()) {
            return null;
        }

        if ($places->isFeatureId($identifier)) {
            $identifier = $places->placeIdFromFeatureId($identifier) ?? $identifier;
        }

        if ($places->isFeatureId($identifier)) {
            $cid = $places->cidFromFeatureId($identifier);

            return $cid ? 'https://www.google.com/maps?cid='.$cid : null;
        }

        return 'https://search.google.com/local/writereview?placeid='.rawurlencode($identifier);
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

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function orders()
    {
        return $this->hasMany(MenuOrder::class);
    }
}
