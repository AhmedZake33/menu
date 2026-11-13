<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;

class DemoMenuController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $slug = config('marketing.demo_restaurant_slug');
        $restaurant = filled($slug) ? Restaurant::where('slug', $slug)->first() : null;

        if ($restaurant?->isAvailable() && $restaurant->hasModule('public_menu')
            && $restaurant->menuPages()->where('is_active', true)->exists()) {
            return redirect()->route('public.restaurant', $restaurant);
        }

        return redirect()->to(route('public.features').'#menu-preview')->with('demo_unavailable', true);
    }
}
