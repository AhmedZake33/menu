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

test('google review url falls back to null when no place id exists', function () {
    $restaurant = Restaurant::factory()->create([
        'name' => 'Cafe Rania',
        'address' => 'Downtown Cairo',
        'google_place_id' => null,
        'map_url' => null,
    ]);

    expect($restaurant->googlePlaceId())->toBeNull()
        ->and($restaurant->hasGoogleReviewLink())->toBeFalse()
        ->and($restaurant->googleReviewUrl())->toBeNull();
});

test('google review qr code is refused when the place id is missing', function () {
    $restaurant = Restaurant::factory()->create(['google_place_id' => null, 'map_url' => null]);
    $admin = reviewQrAdmin($restaurant);

    $this->actingAs($admin)
        ->get(route('dashboard.restaurant.google-review-qr', 'svg'))
        ->assertStatus(409);
});

test('the maps link is enough to build a review link', function () {
    $restaurant = Restaurant::factory()->create([
        'google_place_id' => null,
        'map_url' => 'https://www.google.com/maps/place/Omega+Cafe/data=!4m8!3m7!1s0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b!9m1!1b1!16s%2Fg%2F11c5r5',
    ]);

    expect($restaurant->hasGoogleReviewLink())->toBeTrue()
        ->and($restaurant->googleReviewUrl())->toBe('https://search.google.com/local/writereview?placeid=0x14f2c6b5a2a3b1e9%3A0x9a8b7c6d5e4f3a2b');
});

test('place id can be decoded from a url encoded maps link', function () {
    $restaurant = Restaurant::factory()->create([
        'google_place_id' => null,
        'map_url' => 'https://maps.app.goo.gl/abcd?place_id=ChIJN1t_tDeuEmsRUsoyG83frY4',
    ]);

    expect($restaurant->googlePlaceId())->toBe('ChIJN1t_tDeuEmsRUsoyG83frY4');
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

test('settings page prompts for the maps link when the review qr is not ready', function () {
    $restaurant = Restaurant::factory()->create(['google_place_id' => null, 'map_url' => null]);
    $admin = reviewQrAdmin($restaurant);

    $this->actingAs($admin)
        ->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()
        ->assertSee('QR التقييم على Google Maps')
        ->assertSee('كود التقييم هيظهر هنا أول ما تحفظ رابط المطعم على Google Maps.')
        ->assertDontSee(route('dashboard.restaurant.google-review-qr', 'svg'), false);
});

test('super admin can set the review place id for a client', function () {
    $restaurant = Restaurant::factory()->create();
    $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin, 'restaurant_id' => null]);

    $this->actingAs($superAdmin)->put(route('admin.restaurants.update', $restaurant), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'map_url' => 'https://maps.app.goo.gl/abcd?place_id=ChIJN1t_tDeuEmsRUsoyG83frY4',
        'google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->hasGoogleReviewLink())->toBeTrue();
});
