<?php

use App\Enums\UserRole;
use App\Models\MenuPage;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('expired restaurant cannot open dashboard or public menu', function () {
    $restaurant = Restaurant::factory()->create(['expires_at' => now()->subMinute()]);
    $admin = User::factory()->create([
        'role' => UserRole::RestaurantAdmin,
        'restaurant_id' => $restaurant->id,
    ]);
    MenuPage::create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Main',
        'slug' => 'main',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->actingAs($admin)->get(route('dashboard.home'))->assertForbidden();
    $this->get(route('public.restaurant', $restaurant))->assertNotFound();
});

test('super admin can set and remove restaurant expiration', function () {
    $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin, 'restaurant_id' => null]);
    $restaurant = Restaurant::factory()->create();

    $this->actingAs($superAdmin)->put(route('admin.restaurants.update', $restaurant), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'is_active' => 1,
        'expires_at' => now()->addMonth()->format('Y-m-d H:i:s'),
    ])->assertSessionHasNoErrors();

    expect($restaurant->fresh()->expires_at)->not->toBeNull();

    $this->actingAs($superAdmin)->put(route('admin.restaurants.update', $restaurant), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'is_active' => 1,
        'expires_at' => null,
    ])->assertSessionHasNoErrors();

    expect($restaurant->fresh()->expires_at)->toBeNull();
});
