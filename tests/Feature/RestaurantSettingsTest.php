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

test('location picker shows the saved location on a keyless google maps embed', function () {
    $restaurant = Restaurant::factory()->create(['map_latitude' => '30.0444200', 'map_longitude' => '31.2357100']);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()
        // no API key anywhere: the embed endpoint is keyless
        ->assertSee('https://www.google.com/maps?q=30.0444200%2C31.2357100&amp;output=embed', false)
        ->assertDontSee('GOOGLE_MAPS_API_KEY')
        ->assertDontSee('maps.googleapis.com', false);
});

test('location picker reads coordinates out of a pasted maps link', function () {
    $restaurant = Restaurant::factory()->create([
        'map_latitude' => null,
        'map_longitude' => null,
        'map_url' => 'https://www.google.com/maps/place/Omega/@30.0444,31.2357,17z/data=!4m6!3m5!1s0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b!8m2!3d30.0511!4d31.2402',
    ]);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    // !3d/!4d wins over the @lat,lng view centre
    $this->actingAs($admin)->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()
        ->assertSee('https://www.google.com/maps?q=30.0511%2C31.2402&amp;output=embed', false);
});

test('location picker falls back to the address when there is no link yet', function () {
    $restaurant = Restaurant::factory()->create([
        'name' => 'Cafe Rania',
        'address' => 'Downtown Cairo',
        'map_latitude' => null,
        'map_longitude' => null,
        'map_url' => null,
    ]);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()
        ->assertSee('https://www.google.com/maps?q=Downtown%20Cairo&amp;output=embed', false);
});
