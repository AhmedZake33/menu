<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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

test('location picker is link only and shows no map of its own', function () {
    $restaurant = Restaurant::factory()->create(['map_latitude' => '30.0444200', 'map_longitude' => '31.2357100']);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()
        ->assertSee('رابط المطعم على Google Maps')
        ->assertSee('name="map_url"', false)
        // the location card is an input only: no embed, no preview, no manual pin
        ->assertDontSee('maps?q=', false)
        ->assertDontSee('<iframe', false)
        ->assertDontSee('name="map_latitude"', false)
        ->assertDontSee('name="map_longitude"', false);
});

test('a pasted maps link is the only input and its coordinates are read on save', function () {
    $restaurant = Restaurant::factory()->create(['map_latitude' => null, 'map_longitude' => null]);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'currency' => 'EGP',
        // !3d/!4d wins over the @lat,lng view centre
        'map_url' => 'https://www.google.com/maps/place/Omega/@30.0444,31.2357,17z/data=!4m6!3m5!1s0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b!8m2!3d30.0511!4d31.2402',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->map_latitude)->toBe('30.0511000')
        ->and($restaurant->fresh()->map_longitude)->toBe('31.2402000');
});

test('a percent encoded coordinate pair in a pasted link is still read', function () {
    Http::fake(['*' => Http::response('', 404)]);

    $restaurant = Restaurant::factory()->create(['map_latitude' => null, 'map_longitude' => null]);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'currency' => 'EGP',
        'map_url' => 'https://www.google.com/maps/search/?api=1&query=30.0444200%2C31.2357100',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->map_latitude)->toBe('30.0444200')
        ->and($restaurant->fresh()->map_longitude)->toBe('31.2357100');
});

test('clearing the link clears the pin it produced', function () {
    $restaurant = Restaurant::factory()->create(['map_latitude' => '30.0444200', 'map_longitude' => '31.2357100']);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'currency' => 'EGP',
        'map_url' => '',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->map_latitude)->toBeNull()
        ->and($restaurant->fresh()->map_longitude)->toBeNull();
});

test('a link without coordinates leaves the saved pin alone', function () {
    Http::fake(['*' => Http::response('', 404)]);

    $restaurant = Restaurant::factory()->create(['map_latitude' => '30.0444200', 'map_longitude' => '31.2357100']);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'currency' => 'EGP',
        'map_url' => 'https://maps.app.goo.gl/aBcDeFgHiJ',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->map_latitude)->toBe('30.0444200')
        ->and($restaurant->fresh()->map_longitude)->toBe('31.2357100');
});
