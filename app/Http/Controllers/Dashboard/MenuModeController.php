<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Lets a client choose how their public link shows the menu: the digital menu or their own pictures. */
class MenuModeController extends Controller
{
    public function edit(): View
    {
        $restaurant = request()->user()->restaurant;

        return view('dashboard.menu-mode', [
            'restaurant' => $restaurant,
            'modes' => config('modules.menu_modes'),
            'current' => $restaurant->usesImageMenu() ? 'images' : 'digital',
            'imagesCount' => $restaurant->menuImages()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;
        $data = $request->validate(['menu_mode' => ['required', Rule::in(array_keys(config('modules.menu_modes')))]]);
        $old = $restaurant->menu_mode;

        $restaurant->update(['menu_mode' => $data['menu_mode']]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'restaurant_id' => $restaurant->id,
            'action' => 'restaurant.menu-mode.updated',
            'subject_type' => $restaurant::class,
            'subject_id' => $restaurant->id,
            'old_values' => ['menu_mode' => $old],
            'new_values' => ['menu_mode' => $restaurant->menu_mode],
            'ip_address' => $request->ip(),
        ]);

        if ($restaurant->usesImageMenu()) {
            return redirect()->route('dashboard.menu-images.index')->with('success', 'تم اختيار المنيو بالصور. ارفع صور المنيو وهتظهر لعملائك على نفس الرابط والـ QR.');
        }

        return redirect()->route('dashboard.menu-mode.edit')->with('success', 'تم اختيار المنيو الرقمي.');
    }
}
