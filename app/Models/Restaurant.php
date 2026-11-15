<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Services\GooglePlaceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
     * Whether this client can use a module right now. Anything not listed in
     * config/modules.php is off, and so is every digital-menu section while the client
     * is on the image menu (their data is kept and comes back when they switch).
     */
    public function hasModule(string $key): bool
    {
        if ($this->usesImageMenu() && in_array($key, config('modules.digital_only', []), true)) {
            return false;
        }

        return $this->moduleEnabled($key);
    }

    /**
     * What the admin switched on for this client, ignoring the menu mode. The admin form
     * and subscription summaries read this so that browsing a client who is on the image
     * menu never saves the hidden digital sections as "off".
     *
     * "orders" lives in the ordering_enabled column, which the public menu already reads,
     * so there is a single source of truth for it.
     */
    public function moduleEnabled(string $key): bool
    {
        $definition = config("modules.list.$key");

        if (! $definition) {
            return false;
        }

        if ($key === 'orders') {
            return (bool) $this->ordering_enabled;
        }

        $saved = $this->modules ?? [];

        // "files" replaced the narrower "item_images"; honour a choice saved under the old name.
        if ($key === 'files' && ! array_key_exists('files', $saved) && array_key_exists('item_images', $saved)) {
            return (bool) $saved['item_images'];
        }

        return (bool) ($saved[$key] ?? $definition['default']);
    }

    /** @return array<string, bool> */
    public function moduleStates(): array
    {
        return collect(config('modules.list'))->map(fn ($definition, $key) => $this->moduleEnabled($key))->all();
    }

    /** Whether the public link shows uploaded menu pictures instead of the digital menu. */
    public function usesImageMenu(): bool
    {
        return $this->menu_mode === 'images' && $this->moduleEnabled('image_menu');
    }

    /** A client created through /register, still on the free trial the sign-up gave it. */
    public function isTrial(): bool
    {
        return (bool) data_get($this->settings, 'trial', false);
    }

    /** Whole days left on the subscription, or null when it never expires. */
    public function daysLeft(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return $this->expires_at->isPast() ? 0 : (int) ceil(now()->floatDiffInDays($this->expires_at));
    }

    /**
     * Public URL of an uploaded file, or null when there is none or this client's
     * "files" module is off. Views show uploads only through this.
     */
    public function fileUrl(?string $path): ?string
    {
        return $path && $this->hasModule('files') ? Storage::url($path) : null;
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

    public function administrators()
    {
        return $this->users()->where('role', UserRole::RestaurantAdmin->value)->orderBy('id');
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

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function menuImages()
    {
        return $this->hasMany(MenuImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
