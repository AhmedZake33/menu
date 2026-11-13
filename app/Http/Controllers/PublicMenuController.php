<?php

namespace App\Http\Controllers;

use App\Models\MenuPage;
use App\Models\MenuView;
use App\Models\Restaurant;
use App\Services\MenuPricingService;

class PublicMenuController extends Controller
{
    public function __construct(private MenuPricingService $pricing) {}

    public function restaurant(Restaurant $restaurant)
    {
        abort_unless($restaurant->isAvailable() && $restaurant->hasModule('public_menu'), 404);

        $menuPage = $restaurant->menuPages()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? $restaurant->menuPages()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->firstOrFail();

        return $this->renderMenu($restaurant, $menuPage);
    }

    public function menu(Restaurant $restaurant, MenuPage $menuPage)
    {
        abort_unless($restaurant->isAvailable() && $restaurant->hasModule('public_menu') && $menuPage->is_active && $menuPage->restaurant_id === $restaurant->id, 404);

        return $this->renderMenu($restaurant, $menuPage);
    }

    private function renderMenu(Restaurant $restaurant, MenuPage $menuPage)
    {
        $menuPage->load(['theme', 'categories' => fn ($q) => $q->where('is_active', true)->with(['items' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])]);
        $menuPages = $restaurant->menuPages()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->get();
        $mapCoordinates = $restaurant->map_latitude && $restaurant->map_longitude
            ? $restaurant->map_latitude.','.$restaurant->map_longitude
            : null;
        $mapQuery = $mapCoordinates ?: trim($restaurant->address ?: $restaurant->name);
        $mapDirectionsUrl = $mapCoordinates
            ? 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($mapCoordinates)
            : ($restaurant->map_url ?: ($mapQuery ? 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($mapQuery) : null));

        MenuView::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $menuPage->id, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'referrer' => request()->headers->get('referer'), 'viewed_at' => now()]);

        $offers = $this->pricing->activeOffers($restaurant);
        $prices = $this->pricing->prices($restaurant, $menuPage->categories->flatMap->items, $offers);

        return view('public.menu', compact('restaurant', 'menuPage', 'menuPages', 'mapDirectionsUrl', 'offers', 'prices'));
    }
}
