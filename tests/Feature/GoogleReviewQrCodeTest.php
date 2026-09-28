<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reviewQrAdmin(Restaurant $restaurant): User
{
    return User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);
}

test('restaurant admin can download the google review qr code', function () {
    $restaurant = Restaurant::factory()->create(['google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4']);
    $admin = reviewQrAdmin($restaurant);

    $this->actingAs($admin)
        ->get(route('dashboard.restaurant.google-review-qr', 'svg'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('Content-Disposition', 'inline; filename="'.$restaurant->slug.'-google-review.svg"');
});

test('google review qr code rejects unsupported formats', function () {
    $admin = reviewQrAdmin(Restaurant::factory()->create());

    $this->actingAs($admin)
        ->get(route('dashboard.restaurant.google-review-qr', 'gif'))
        ->assertNotFound();
});

test('google review qr code requires authentication', function () {
    $restaurant = Restaurant::factory()->create();

    $this->get(route('dashboard.restaurant.google-review-qr', 'svg'))->assertRedirect(route('login'));
});

test('google review url uses the place id when available', function () {
    $restaurant = Restaurant::factory()->create(['google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4']);

    expect($restaurant->googlePlaceId())->toBe('ChIJN1t_tDeuEmsRUsoyG83frY4')
        ->and($restaurant->googleReviewUrl())->toBe('https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4');
});

test('google place id is extracted from the map url when the column is empty', function () {
    $restaurant = Restaurant::factory()->create([
        'google_place_id' => null,
        'map_url' => 'https://www.google.com/maps/place/Omega/@30.04,31.23,17z/data=!4m6!3m5!1s0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b!8m2!3d30.04!4d31.23!16s%2Fg%2F11c5r5',
    ]);

    expect($restaurant->googlePlaceId())->toBe('0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b')
        ->and($restaurant->googleReviewUrl())->toStartWith('https://search.google.com/local/writereview?placeid=0x14f2c6b5a2a3b1e9');
});

test('google review url falls back to a maps search when no place id exists', function () {
    $restaurant = Restaurant::factory()->create([
        'name' => 'Cafe Rania',
        'address' => 'Downtown Cairo',
        'google_place_id' => null,
        'map_url' => null,
    ]);

    expect($restaurant->googlePlaceId())->toBeNull()
        ->and($restaurant->googleReviewUrl())->toBe('https://www.google.com/maps/search/?api=1&query=Cafe%20Rania%20Downtown%20Cairo');
});

test('restaurant admin can save the google place id', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = reviewQrAdmin($restaurant);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'currency' => 'EGP',
        'google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->google_place_id)->toBe('ChIJN1t_tDeuEmsRUsoyG83frY4');
});

test('google place id must be a valid identifier', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = reviewQrAdmin($restaurant);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'currency' => 'EGP',
        'google_place_id' => 'https://evil.example.com/<script>',
    ])->assertSessionHasErrors('google_place_id');

    expect($restaurant->fresh()->google_place_id)->toBeNull();
});

test('restaurant settings page shows the review qr card', function () {
    $restaurant = Restaurant::factory()->create(['google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4']);
    $admin = reviewQrAdmin($restaurant);

    $this->actingAs($admin)
        ->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()
        ->assertSee('QR التقييم على Google Maps')
        ->assertSee(route('dashboard.restaurant.google-review-qr', 'svg'), false)
        ->assertSee('ChIJN1t_tDeuEmsRUsoyG83frY4');
});
