<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRestaurantSettingsRequest;
use App\Models\ActivityLog;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RestaurantSettingsController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function edit(): View
    {
        return view('dashboard.restaurant-settings', ['restaurant' => request()->user()->restaurant]);
    }

    public function update(UpdateRestaurantSettingsRequest $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;
        $oldValues = $restaurant->toArray();
        $data = $request->safe()->except(['logo', 'cover_image']);
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
}
