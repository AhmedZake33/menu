<?php

use App\Enums\UserRole;
use App\Models\MenuPage;
use App\Models\Restaurant;
use App\Models\User;

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create([
        'name' => 'Details Cafe',
        'description' => 'A full restaurant description',
        'phone' => '01012345678',
        'whatsapp' => '201012345678',
        'email' => 'contact@details.test',
        'address' => 'Details Street Cairo',
        'map_url' => 'https://www.google.com/maps/place/details',
        'map_latitude' => '30.0444200',
        'map_longitude' => '31.2357100',
        'google_place_id' => 'ChIJDetails',
        'website_url' => 'https://details.test',
        'facebook_url' => 'https://facebook.com/details-cafe',
        'instagram_url' => 'https://instagram.com/details-cafe',
        'tiktok_url' => 'https://tiktok.com/@details-cafe',
        'tables_count' => 12,
        'expires_at' => now()->addYear(),
        'settings' => [
            'brand_color' => '#334155',
            'limits' => ['max_menu_pages' => 7],
            'cashier' => ['tax_percent' => 14, 'service_percent' => 10, 'allow_discount' => false, 'payment_methods' => ['wallet'], 'receipt_footer' => 'Details receipt footer'],
        ],
    ]);
    $this->manager = User::factory()->create([
        'role' => UserRole::RestaurantAdmin,
        'restaurant_id' => $this->restaurant->id,
        'name' => 'Details Manager',
        'email' => 'manager@details.test',
    ]);
});

test('restaurant details show restaurant manager subscription and settings for each authorized role', function (bool $superAdmin) {
    $user = $superAdmin ? User::factory()->create(['role' => UserRole::SuperAdmin]) : $this->manager;
    $url = $superAdmin ? route('admin.restaurants.show', $this->restaurant) : route('dashboard.restaurant-settings.show');

    $this->actingAs($user)->get($url)->assertOk()->assertSee([
        'Details Cafe', 'Details Manager', 'manager@details.test',
        'A full restaurant description', '01012345678', '201012345678',
        'contact@details.test', 'Details Street Cairo',
        'https://www.google.com/maps/place/details', '30.0444200', '31.2357100', 'ChIJDetails',
        'https://details.test', 'https://facebook.com/details-cafe',
        'https://instagram.com/details-cafe', 'https://tiktok.com/@details-cafe',
        'EGP', $this->restaurant->expires_at->format('Y/m/d h:i A'), '#334155',
        'محفظة إلكترونية', 'Details receipt footer', 'غير محدود',
    ])->assertSee($superAdmin ? route('admin.restaurants.edit', $this->restaurant) : route('dashboard.restaurant-settings.edit'));
})->with([true, false]);

test('client details always use the signed in restaurant and cannot access admin details', function () {
    $other = Restaurant::factory()->create(['name' => 'Foreign Restaurant']);
    User::factory()->create([
        'role' => UserRole::RestaurantAdmin, 'restaurant_id' => $other->id,
        'name' => 'Foreign Manager', 'email' => 'foreign-manager@details.test',
    ]);

    $this->actingAs($this->manager)
        ->get(route('dashboard.restaurant-settings.show', ['restaurant_id' => $other->id, 'restaurant' => $other->slug]))
        ->assertOk()->assertSee('manager@details.test')
        ->assertDontSee('Foreign Manager')->assertDontSee('foreign-manager@details.test');

    $this->get(route('admin.restaurants.show', $other))->assertForbidden();
});

test('details include all nondeleted restaurant administrators and exclude other roles', function () {
    User::factory()->create([
        'restaurant_id' => $this->restaurant->id, 'role' => UserRole::RestaurantAdmin,
        'name' => 'Second Manager', 'email' => 'second@details.test', 'is_active' => false,
    ]);
    $deleted = User::factory()->create([
        'restaurant_id' => $this->restaurant->id, 'role' => UserRole::RestaurantAdmin,
        'email' => 'deleted@details.test',
    ]);
    $deleted->delete();
    $super = User::factory()->create([
        'restaurant_id' => $this->restaurant->id, 'role' => UserRole::SuperAdmin,
        'email' => 'platform@details.test',
    ]);

    $this->actingAs($super)->get(route('admin.restaurants.show', $this->restaurant))
        ->assertOk()->assertSee(['Details Manager', 'Second Manager', 'second@details.test', 'معطل'])
        ->assertDontSee('deleted@details.test')->assertDontSee('platform@details.test');
});

test('details handle a restaurant without a manager or optional information', function () {
    $restaurant = Restaurant::factory()->create();
    $super = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($super)->get(route('admin.restaurants.show', $restaurant))
        ->assertOk()->assertSee(['لا يوجد حساب مدير مرتبط بهذا المطعم.', 'غير مسجل', 'بدون انتهاء']);
});

test('details respect authentication roles and the restaurant settings module', function () {
    $this->get(route('admin.restaurants.show', $this->restaurant))->assertRedirect(route('login'));
    $this->get(route('dashboard.restaurant-settings.show'))->assertRedirect(route('login'));

    $super = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($super)->get(route('dashboard.restaurant-settings.show'))->assertForbidden();

    $this->restaurant->update(['modules' => ['restaurant_settings' => false]]);
    $this->actingAs($this->manager)->get(route('dashboard.restaurant-settings.show'))->assertNotFound();
    $this->get(route('dashboard.home'))->assertDontSee(route('dashboard.restaurant-settings.show'));
    $this->actingAs($super)->get(route('admin.restaurants.show', $this->restaurant))->assertOk();
});

test('details are linked from client and admin settings and the restaurant list', function () {
    $this->actingAs($this->manager)->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()->assertSee(route('dashboard.restaurant-settings.show'));
    $super = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($super)->get(route('admin.restaurants.index'))
        ->assertOk()->assertSee(route('admin.restaurants.show', $this->restaurant));
    $this->get(route('admin.restaurants.edit', $this->restaurant))
        ->assertOk()->assertSee(route('admin.restaurants.show', $this->restaurant));
});

test('public menu does not expose restaurant manager account details', function () {
    $page = MenuPage::create([
        'restaurant_id' => $this->restaurant->id, 'name' => 'Menu', 'slug' => 'menu', 'is_default' => true,
    ]);
    $page->theme()->create(['restaurant_id' => $this->restaurant->id]);

    $this->get(route('public.restaurant', $this->restaurant))->assertOk()
        ->assertDontSee('Details Manager')->assertDontSee('manager@details.test');
});
