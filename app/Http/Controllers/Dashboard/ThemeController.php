<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMenuThemeRequest;
use App\Models\MenuPage;

class ThemeController extends Controller
{
    public function edit(MenuPage $menuPage)
    {
        $this->authorize('update', $menuPage);
        $menuPage->load('theme');

        return view('dashboard.theme', compact('menuPage'));
    }

    public function update(UpdateMenuThemeRequest $request, MenuPage $menuPage)
    {
        $data = $request->validated();
        foreach (['show_item_images', 'show_descriptions', 'show_prices', 'show_category_images', 'sticky_categories', 'enable_search', 'enable_category_filter', 'enable_dark_mode'] as $key) {
            $data[$key] = $request->boolean($key);
        }
        $menuPage->theme->update($data);

        return back()->with('success', 'تم حفظ تصميم المنيو.');
    }
}
