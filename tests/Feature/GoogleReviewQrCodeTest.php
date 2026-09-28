<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\GooglePlaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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

    expect($restaurant->googlePlaceId())->toBe('0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b');
});

test('a feature id is converted to a cid maps link instead of a writereview link', function () {
    // writereview?placeid= requires a real Place ID (ChIJ...); a 0x..:0x.. feature id is not one.
    $restaurant = Restaurant::factory()->create([
        'google_place_id' => '0x14f7a512ea1236e1:0x9cf70ce32ab5cc62',
    ]);

    expect($restaurant->googleReviewUrl())
        ->toBe('https://www.google.com/maps?cid=11310523158977956962')
        ->and($restaurant->googleReviewUrl())->not->toContain('writereview');
});

test('feature id to cid conversion matches known google values', function () {
    $service = app(GooglePlaceService::class);

    // Documented pairing: Place ID ChIJ6-G_3DqAUocRw5ctKLeX2yI <-> CID 2511768030098069443
    expect($service->cidFromFeatureId('0x8752803adcbfe1eb:0x22db97b7282d97c3'))->toBe('2511768030098069443')
        ->and($service->cidFromFeatureId('ChIJ6-G_3DqAUocRw5ctKLeX2yI'))->toBeNull();
});

test('a real place id still uses the direct writereview link', function () {
    $restaurant = Restaurant::factory()->create([
        'google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
    ]);

    expect($restaurant->googleReviewUrl())
        ->toBe('https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4');
});

test('a place id is preferred over a feature id in the same link', function () {
    $service = app(GooglePlaceService::class);

    expect($service->extract('https://maps.app.goo.gl/abcd?place_id=ChIJN1t_tDeuEmsRUsoyG83frY4'))
        ->toBe('ChIJN1t_tDeuEmsRUsoyG83frY4')
        ->and($service->featureId('https://maps.app.goo.gl/abcd?place_id=ChIJN1t_tDeuEmsRUsoyG83frY4'))
        ->toBeNull();
});

test('a bare google maps cid is not treated as a place id', function () {
    $restaurant = Restaurant::factory()->create([
        'google_place_id' => null,
        'map_url' => 'https://www.google.com/maps?cid=1234567890',
    ]);

    expect($restaurant->googlePlaceId())->toBeNull()
        ->and($restaurant->hasGoogleReviewLink())->toBeFalse();
});

test('a short maps link without place data does not enable the review qr', function () {
    $restaurant = Restaurant::factory()->create([
        'google_place_id' => null,
        'map_url' => 'https://maps.app.goo.gl/aBcDeFgHiJ',
    ]);

    expect(app(GooglePlaceService::class)->extract($restaurant->map_url))->toBeNull()
        ->and($restaurant->googlePlaceId())->toBeNull()
        ->and($restaurant->hasGoogleReviewLink())->toBeFalse();
});

test('the google place service ignores non google links', function () {
    $service = app(GooglePlaceService::class);

    expect($service->resolve('https://evil.example.com/maps/place/abc'))->toBeNull()
        ->and($service->extract('https://evil.example.com/place_id=ChIJfake'))->toBeNull();
});

test('the google place service extracts a place id from a full maps link', function () {
    $service = app(GooglePlaceService::class);

    expect($service->resolve('https://www.google.com/maps/place/Omega/data=!4m6!3m5!1s0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b!8m2'))
        ->toBe('0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b');
});

test('saving a maps link resolves the place id even when the field was left empty', function () {
    Http::fake([
        'maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://www.google.com/maps/place/Omega/data=!4m6!3m5!1s0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b!8m2']),
        '*' => Http::response('', 404),
    ]);

    $restaurant = Restaurant::factory()->create(['google_place_id' => null, 'map_url' => null]);
    $admin = reviewQrAdmin($restaurant);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'currency' => 'EGP',
        'map_url' => 'https://maps.app.goo.gl/aBcDeFgHiJ',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->google_place_id)->toBe('0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b')
        ->and($restaurant->fresh()->googleReviewUrl())->toBe('https://www.google.com/maps?cid=11136131312779213355');
});

test('an unresolvable maps link is saved without breaking the request', function () {
    Http::fake(['*' => Http::response('', 404)]);

    $restaurant = Restaurant::factory()->create(['google_place_id' => null, 'map_url' => null]);
    $admin = reviewQrAdmin($restaurant);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'currency' => 'EGP',
        'map_url' => 'https://maps.app.goo.gl/notARealLink',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->map_url)->toBe('https://maps.app.goo.gl/notARealLink')
        ->and($restaurant->fresh()->google_place_id)->toBeNull();
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

test('the maps link is enough to build a working review link', function () {
    $restaurant = Restaurant::factory()->create([
        'google_place_id' => null,
        'map_url' => 'https://www.google.com/maps/place/Omega+Cafe/data=!4m8!3m7!1s0x14f2c6b5a2a3b1e9:0x9a8b7c6d5e4f3a2b!9m1!1b1!16s%2Fg%2F11c5r5',
    ]);

    expect($restaurant->hasGoogleReviewLink())->toBeTrue()
        ->and($restaurant->googleReviewUrl())->toBe('https://www.google.com/maps?cid=11136131312779213355');
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
