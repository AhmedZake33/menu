<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RestaurantController;
use App\Http\Controllers\Dashboard\CashierController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\ItemController;
use App\Http\Controllers\Dashboard\MenuImageController;
use App\Http\Controllers\Dashboard\MenuModeController;
use App\Http\Controllers\Dashboard\MenuOrderController;
use App\Http\Controllers\Dashboard\MenuPageController;
use App\Http\Controllers\Dashboard\OfferController;
use App\Http\Controllers\Dashboard\QrCodeController;
use App\Http\Controllers\Dashboard\ReservationController;
use App\Http\Controllers\Dashboard\RestaurantSettingsController;
use App\Http\Controllers\Dashboard\ThemeController;
use App\Http\Controllers\DemoMenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\PublicReservationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.features')->name('public.features');
Route::get('/demo', DemoMenuController::class)->name('public.demo');
Route::get('/r/{restaurant}', [PublicMenuController::class, 'restaurant'])->name('public.restaurant');
Route::post('/r/{restaurant}/orders/code', [PublicOrderController::class, 'sendCode'])->name('public.orders.code');
Route::post('/r/{restaurant}/orders/confirm', [PublicOrderController::class, 'confirm'])->name('public.orders.confirm');
Route::post('/r/{restaurant}/reservations', [PublicReservationController::class, 'store'])->middleware('throttle:6,1')->name('public.reservations.store');
Route::get('/r/{restaurant}/menu/{menuPage}', [PublicMenuController::class, 'menu'])->scopeBindings()->name('public.menu');
Route::get('/menu/{restaurant}', [PublicMenuController::class, 'restaurant'])->name('public.short');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/home', fn () => redirect()->route(request()->user()->isSuperAdmin() ? 'admin.dashboard' : 'dashboard.home'))->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [DashboardController::class, 'super'])->name('dashboard');
        Route::resource('restaurants', RestaurantController::class);
    });

    Route::prefix('dashboard')->name('dashboard.')->middleware(['role:restaurant_admin', 'restaurant.active', 'uploads.allowed'])->group(function () {
        Route::get('/', [DashboardController::class, 'restaurant'])->name('home');
        Route::resource('offers', OfferController::class)->except('show')->middleware('module:offers');
        Route::middleware('module:restaurant_settings')->group(function () {
            Route::get('restaurant-settings/details', [RestaurantSettingsController::class, 'show'])->name('restaurant-settings.show');
            Route::get('restaurant-settings', [RestaurantSettingsController::class, 'edit'])->name('restaurant-settings.edit');
            Route::put('restaurant-settings', [RestaurantSettingsController::class, 'update'])->name('restaurant-settings.update');
        });
        Route::middleware('module:image_menu')->group(function () {
            Route::get('menu-mode', [MenuModeController::class, 'edit'])->name('menu-mode.edit');
            Route::put('menu-mode', [MenuModeController::class, 'update'])->name('menu-mode.update');
            Route::get('menu-images', [MenuImageController::class, 'index'])->name('menu-images.index');
            Route::post('menu-images', [MenuImageController::class, 'store'])->name('menu-images.store');
            Route::patch('menu-images/{menuImage}/move', [MenuImageController::class, 'move'])->name('menu-images.move');
            Route::delete('menu-images/{menuImage}', [MenuImageController::class, 'destroy'])->name('menu-images.destroy');
        });
        Route::resource('menu-pages', MenuPageController::class)->except('show')->parameters(['menu-pages' => 'menuPage'])->middleware('module:menu_pages');
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('module:categories');
        Route::middleware('module:orders')->group(function () {
            Route::get('orders', [MenuOrderController::class, 'index'])->name('orders.index');
            Route::patch('orders/{order}', [MenuOrderController::class, 'update'])->name('orders.update');
        });
        Route::middleware('module:items')->group(function () {
            Route::delete('items/{item}/image', [ItemController::class, 'destroyImage'])->name('items.image.destroy');
            Route::resource('items', ItemController::class)->only(['index', 'store', 'update', 'destroy']);
        });
        Route::middleware('module:theme')->group(function () {
            Route::get('menu-pages/{menuPage}/theme', [ThemeController::class, 'edit'])->name('theme.edit');
            Route::put('menu-pages/{menuPage}/theme', [ThemeController::class, 'update'])->name('theme.update');
        });
        Route::get('restaurant/qr/{format}', [QrCodeController::class, 'restaurant'])->name('restaurant.qr')->middleware('module:qr');
        Route::get('restaurant/google-review-qr/{format}', [QrCodeController::class, 'review'])->name('restaurant.google-review-qr')->middleware('module:google_review');
        Route::get('menu-pages/{menuPage}/qr/{format}', QrCodeController::class)->name('qr')->middleware('module:qr');
        Route::middleware('module:reservations')->group(function () {
            Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
            Route::patch('reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
        });
        Route::prefix('cashier')->name('cashier.')->middleware('module:cashier')->group(function () {
            Route::get('/', [CashierController::class, 'index'])->name('index');
            Route::post('sales', [CashierController::class, 'store'])->name('sales.store');
            Route::post('orders/{order}/pay', [CashierController::class, 'pay'])->name('orders.pay');
            Route::get('receipt/{order}', [CashierController::class, 'receipt'])->name('receipt');
            Route::get('report', [CashierController::class, 'report'])->name('report');
            Route::get('settings', [CashierController::class, 'settings'])->name('settings');
            Route::put('settings', [CashierController::class, 'updateSettings'])->name('settings.update');
        });
    });
});

require __DIR__.'/auth.php';
