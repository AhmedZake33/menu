<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('restaurant administrator can update only their restaurant details', function () {
    $restaurant = Restaurant::factory()->create(['name' => 'Old Name']);
    $other = Restaurant::factory()->create(['name' => 'Must Stay']);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => 'Updated Cafe',
        'slug' => 'updated-cafe',
        'description' => 'New description',
        'currency' => 'EGP',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->name)->toBe('Updated Cafe')
        ->and($other->fresh()->name)->toBe('Must Stay');
});

test('restaurant administrator can save a map location from the picker', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'description' => $restaurant->description,
        'currency' => 'EGP',
        'map_latitude' => '30.0444200',
        'map_longitude' => '31.2357100',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->map_latitude)->toBe('30.0444200')
        ->and($restaurant->fresh()->map_longitude)->toBe('31.2357100');
});

test('super admin cannot use restaurant self service settings route', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'restaurant_id' => null]);
    $this->actingAs($admin)->get(route('dashboard.restaurant-settings.edit'))->assertForbidden();
});
