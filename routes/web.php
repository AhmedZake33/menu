<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RestaurantController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\ItemController;
use App\Http\Controllers\Dashboard\MenuOrderController;
use App\Http\Controllers\Dashboard\MenuPageController;
use App\Http\Controllers\Dashboard\QrCodeController;
use App\Http\Controllers\Dashboard\RestaurantSettingsController;
use App\Http\Controllers\Dashboard\ThemeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\PublicOrderController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::get('/r/{restaurant}', [PublicMenuController::class, 'restaurant'])->name('public.restaurant');
Route::post('/r/{restaurant}/orders/code', [PublicOrderController::class, 'sendCode'])->name('public.orders.code');
Route::post('/r/{restaurant}/orders/confirm', [PublicOrderController::class, 'confirm'])->name('public.orders.confirm');
Route::get('/r/{restaurant}/menu/{menuPage}', [PublicMenuController::class, 'menu'])->scopeBindings()->name('public.menu');
Route::get('/menu/{restaurant}', [PublicMenuController::class, 'restaurant'])->name('public.short');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/home', fn () => redirect()->route(request()->user()->isSuperAdmin() ? 'admin.dashboard' : 'dashboard.home'))->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [DashboardController::class, 'super'])->name('dashboard');
        Route::resource('restaurants', RestaurantController::class)->except('show');
    });

    Route::prefix('dashboard')->name('dashboard.')->middleware(['role:restaurant_admin', 'restaurant.active'])->group(function () {
        Route::get('/', [DashboardController::class, 'restaurant'])->name('home');
        Route::get('restaurant-settings', [RestaurantSettingsController::class, 'edit'])->name('restaurant-settings.edit');
        Route::put('restaurant-settings', [RestaurantSettingsController::class, 'update'])->name('restaurant-settings.update');
        Route::resource('menu-pages', MenuPageController::class)->except('show')->parameters(['menu-pages' => 'menuPage']);
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('orders', [MenuOrderController::class, 'index'])->name('orders.index');
        Route::patch('orders/{order}', [MenuOrderController::class, 'update'])->name('orders.update');
        Route::delete('items/{item}/image', [ItemController::class, 'destroyImage'])->name('items.image.destroy');
        Route::resource('items', ItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('menu-pages/{menuPage}/theme', [ThemeController::class, 'edit'])->name('theme.edit');
        Route::put('menu-pages/{menuPage}/theme', [ThemeController::class, 'update'])->name('theme.update');
        Route::get('restaurant/qr/{format}', [QrCodeController::class, 'restaurant'])->name('restaurant.qr');
        Route::get('menu-pages/{menuPage}/qr/{format}', QrCodeController::class)->name('qr');
    });
});

require __DIR__.'/auth.php';
