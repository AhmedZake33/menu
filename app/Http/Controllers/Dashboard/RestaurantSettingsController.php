<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRestaurantSettingsRequest;
use App\Models\ActivityLog;
use App\Models\Restaurant;
use App\Services\GooglePlaceService;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RestaurantSettingsController extends Controller
{
    public function __construct(private ImageService $images, private GooglePlaceService $googlePlaces) {}

    public function edit(): View
    {
        $restaurant = request()->user()->restaurant;

        return view('dashboard.restaurant-settings', [
            'restaurant' => $restaurant,
            'reviewUrl' => $restaurant->googleReviewUrl(),
        ]);
    }

    public function update(UpdateRestaurantSettingsRequest $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;
        $oldValues = $restaurant->toArray();
        $data = $request->safe()->except(['logo', 'cover_image']);
        $data['google_place_id'] = $this->resolvePlaceId($data, $restaurant);
        $data += $this->resolveCoordinates($data);
        $data['logo'] = $this->images->replace($request->file('logo'), $restaurant->logo, "restaurants/{$restaurant->id}/logo");
        $data['cover_image'] = $this->images->replace($request->file('cover_image'), $restaurant->cover_image, "restaurants/{$restaurant->id}/covers");
        $restaurant->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'restaurant_id' => $restaurant->id,
            'action' => 'restaurant.settings.updated',
            'subject_type' => $restaurant::class,
            'subject_id' => $restaurant->id,
            'description' => 'قام مدير المطعم بتحديث بيانات المطعم.',
            'old_values' => $oldValues,
            'new_values' => $restaurant->fresh()->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'تم حفظ بيانات المطعم بنجاح.');
    }

    private function resolvePlaceId(array $data, Restaurant $restaurant): ?string
    {
        if ($placeId = trim((string) ($data['google_place_id'] ?? null))) {
            return $placeId;
        }

        $mapUrl = array_key_exists('map_url', $data) ? $data['map_url'] : $restaurant->map_url;

        return $this->googlePlaces->resolve($mapUrl) ?? $this->googlePlaces->extract($mapUrl);
    }

    /**
     * A pasted link is the only input on the form, so the pin has to follow it rather
     * than wait for the client to copy coordinates across. Clearing the link clears the
     * pin with it, while a link that simply carries no coordinates leaves the saved pin
     * alone rather than silently dropping a good location.
     *
     * @return array{map_latitude?: string|null, map_longitude?: string|null}
     */
    private function resolveCoordinates(array $data): array
    {
        if (! array_key_exists('map_url', $data)) {
            return [];
        }

        if (! $data['map_url']) {
            return ['map_latitude' => null, 'map_longitude' => null];
        }

        if (! $coordinates = $this->googlePlaces->coordinates($data['map_url'])) {
            return [];
        }

        return [
            'map_latitude' => number_format($coordinates['lat'], 7, '.', ''),
            'map_longitude' => number_format($coordinates['lng'], 7, '.', ''),
        ];
    }
}
