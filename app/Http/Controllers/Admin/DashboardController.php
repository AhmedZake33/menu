<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\MenuPage;
use App\Models\MenuView;
use App\Models\Restaurant;

class DashboardController extends Controller
{
    public function super()
    {
        return view('admin.dashboard', ['stats' => [
            'المطاعم' => Restaurant::count(),
            'المطاعم النشطة' => Restaurant::where('is_active', true)->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
            'الاشتراكات المنتهية' => Restaurant::whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
            'صفحات المنيو' => MenuPage::count(),
            'التصنيفات' => Category::count(),
            'الأصناف' => Item::count(),
            'المشاهدات' => MenuView::count(),
        ], 'restaurants' => Restaurant::latest()->take(8)->get()]);
    }

    public function restaurant()
    {
        $r = request()->user()->restaurant;

        return view('dashboard', ['restaurant' => $r, 'stats' => ['صفحات المنيو' => $r->menuPages()->count(), 'التصنيفات' => $r->categories()->count(), 'الأصناف' => $r->items()->count(), 'المشاهدات' => $r->menuViews()->count()]]);
    }
}
