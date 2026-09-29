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

test('location picker receives the configured google maps key', function () {
    config()->set('services.google_maps.key', 'test-maps-key-123');
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()
        ->assertSee('data-google-maps-key="test-maps-key-123"', false)
        ->assertDontSee('خريطة Google محتاجة مفتاح API', false);
});

test('location picker degrades to manual coordinates when no google maps key is set', function () {
    config()->set('services.google_maps.key', null);
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $response = $this->actingAs($admin)->get(route('dashboard.restaurant-settings.edit'))->assertOk();

    $response->assertSee('data-google-maps-key=""', false)
        ->assertSee('GOOGLE_MAPS_API_KEY')
        // coordinates must stay editable so the form is still usable without a map
        ->assertSee('name="map_latitude" dir="ltr" step="any"', false)
        ->assertSee('name="map_longitude" dir="ltr" step="any"', false);
});

test('coordinates stay read only while the map is available', function () {
    config()->set('services.google_maps.key', 'test-maps-key-123');
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()
        ->assertSee('name="map_latitude" dir="ltr" step="any" readonly', false);
});
