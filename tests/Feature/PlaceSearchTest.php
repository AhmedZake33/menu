<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\GooglePlaceService;
use App\Services\PlaceSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function placeSearchAdmin(Restaurant $restaurant): User
{
    return User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);
}

function nominatimResults(): array
{
    return [
        [
            'lat' => '30.0469677',
            'lon' => '31.2382388',
            'display_name' => 'Café Riche, El Alfi Street, Cairo, Egypt',
            'name' => 'Café Riche',
        ],
        [
            'lat' => '30.0444200',
            'lon' => '31.2357100',
            'display_name' => 'Café Riche, Downtown, Cairo, Egypt',
            'name' => 'Café Riche',
        ],
    ];
}

function embedHtml(string $placeId = 'ChIJG__pfnpBWBQRhJzaSgwA8wI'): string
{
    // Google ships the resolved place on a single line, so the fixture has to as well.
    $payload = '[["0x1458409aa81d58a5:0x6ce6bf7cd258d6fe","Café Riche, 17 Talaat Harb, Bab Al Louq, Cairo Governorate",'
        .'[30.0469804,31.2382757]],"Café Riche",["El Alfi Street","Cairo","Egypt"],'
        ."\"$placeId\"]";

    return '<html><body><script>function onEmbedLoad(){initEmbed([null,null,null,['.$payload.']);}</script></body></html>';
}

test('restaurant admin can search for a place and pick from the candidates', function () {
    Cache::flush();
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(nominatimResults())]);

    $admin = placeSearchAdmin(Restaurant::factory()->create());

    $this->actingAs($admin)
        ->getJson(route('dashboard.restaurant-settings.place-search', ['q' => 'cafe riche cairo']))
        ->assertOk()
        ->assertJsonCount(2, 'places')
        ->assertJsonPath('places.0.name', 'Café Riche')
        ->assertJsonPath('places.0.address', 'El Alfi Street, Cairo, Egypt')
        ->assertJsonPath('places.0.lat', 30.0469677)
        ->assertJsonPath('places.0.lng', 31.2382388)
        ->assertJsonPath('places.0.label', 'Café Riche, El Alfi Street, Cairo, Egypt');
});

test('place search stays keyless and never needs an api key', function () {
    Cache::flush();
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(nominatimResults())]);

    $admin = placeSearchAdmin(Restaurant::factory()->create());

    $this->actingAs($admin)
        ->getJson(route('dashboard.restaurant-settings.place-search', ['q' => 'cafe riche cairo']))
        ->assertOk()
        ->assertDontSee('maps.googleapis.com', false)
        ->assertDontSee('API_KEY', false);

    Http::assertSent(fn ($request) => $request->url() === 'https://nominatim.openstreetmap.org/search?format=jsonv2&q=cafe%20riche%20cairo&limit=6&addressdetails=1');
});

test('place search refuses a query that is too short to be a place', function () {
    Cache::flush();
    Http::fake();

    $admin = placeSearchAdmin(Restaurant::factory()->create());

    $this->actingAs($admin)
        ->getJson(route('dashboard.restaurant-settings.place-search', ['q' => 'ab']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('q');

    Http::assertNothingSent();
});

test('place search is closed to guests and to the super admin', function () {
    $restaurant = Restaurant::factory()->create();

    $this->getJson(route('dashboard.restaurant-settings.place-search', ['q' => 'cafe riche']))
        ->assertUnauthorized();

    $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin, 'restaurant_id' => null]);

    $this->actingAs($superAdmin)
        ->getJson(route('dashboard.restaurant-settings.place-search', ['q' => 'cafe riche']))
        ->assertForbidden();
});

test('an empty place search returns a message instead of an error', function () {
    Cache::flush();
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $admin = placeSearchAdmin(Restaurant::factory()->create());

    $this->actingAs($admin)
        ->getJson(route('dashboard.restaurant-settings.place-search', ['q' => 'nowhere at all']))
        ->assertOk()
        ->assertJsonCount(0, 'places')
        ->assertJsonPath('message', 'مفيش نتايج للبحث ده. جرّب اسم أطول أو اسم الشارع.');
});

test('an unreachable place search degrades to no results', function () {
    Cache::flush();
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response('gateway timeout', 504)]);

    $admin = placeSearchAdmin(Restaurant::factory()->create());

    $this->actingAs($admin)
        ->getJson(route('dashboard.restaurant-settings.place-search', ['q' => 'cafe riche cairo']))
        ->assertOk()
        ->assertJsonCount(0, 'places');
});

test('a picked place is resolved into a google place id for the review qr', function () {
    Cache::flush();
    Http::fake([
        '*google.com/maps*' => Http::response(embedHtml()),
        'nominatim.openstreetmap.org/*' => Http::response(nominatimResults()),
    ]);

    $admin = placeSearchAdmin(Restaurant::factory()->create());

    $this->actingAs($admin)
        ->postJson(route('dashboard.restaurant-settings.place-id'), [
            'name' => 'Café Riche',
            'address' => 'El Alfi Street, Cairo, Egypt',
        ])
        ->assertOk()
        ->assertJsonPath('place_id', 'ChIJG__pfnpBWBQRhJzaSgwA8wI');
});

test('a searched place id turns on the review link the same way a pasted link does', function () {
    Cache::flush();
    Http::fake([
        '*google.com/maps*' => Http::response(embedHtml()),
        'nominatim.openstreetmap.org/*' => Http::response(nominatimResults()),
    ]);

    $restaurant = Restaurant::factory()->create(['google_place_id' => null, 'map_url' => null]);
    $admin = placeSearchAdmin($restaurant);

    $placeId = $this->actingAs($admin)
        ->postJson(route('dashboard.restaurant-settings.place-id'), ['name' => 'Café Riche', 'address' => 'El Alfi Street, Cairo, Egypt'])
        ->assertOk()
        ->json('place_id');

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'currency' => 'EGP',
        'map_latitude' => '30.0469677',
        'map_longitude' => '31.2382388',
        'map_url' => 'https://www.google.com/maps/search/?api=1&query=cafe+riche&query_place_id='.$placeId,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($restaurant->fresh()->google_place_id)->toBe($placeId)
        ->and($restaurant->fresh()->hasGoogleReviewLink())->toBeTrue();
});

test('a place google cannot pin down still gives a working map without a review qr', function () {
    Cache::flush();
    // A category query comes back as an anonymous candidate list with no place block.
    Http::fake([
        '*google.com/maps*' => Http::response('<html><script>function onEmbedLoad(){initEmbed([null,null,"categorical-search-results-injection"]);}</script></html>'),
        'nominatim.openstreetmap.org/*' => Http::response(nominatimResults()),
    ]);

    $admin = placeSearchAdmin(Restaurant::factory()->create());

    $this->actingAs($admin)
        ->postJson(route('dashboard.restaurant-settings.place-id'), ['name' => 'KFC', 'address' => 'Maadi, Cairo, Egypt'])
        ->assertOk()
        ->assertJsonPath('place_id', null);
});

test('the place id lookup validates the place it is given', function () {
    Cache::flush();
    Http::fake();

    $admin = placeSearchAdmin(Restaurant::factory()->create());

    $this->actingAs($admin)
        ->postJson(route('dashboard.restaurant-settings.place-id'), ['name' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');

    $this->actingAs($admin)
        ->postJson(route('dashboard.restaurant-settings.place-id'), ['name' => str_repeat('a', 151)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');

    Http::assertNothingSent();
});

test('restaurant settings page offers both a search box and the paste box', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = placeSearchAdmin($restaurant);

    $this->actingAs($admin)
        ->get(route('dashboard.restaurant-settings.edit'))
        ->assertOk()
        ->assertSee('دوّر على المطعم')
        ->assertSee('data-place-search', false)
        ->assertSee(route('dashboard.restaurant-settings.place-search'), false)
        ->assertSee(route('dashboard.restaurant-settings.place-id'), false)
        ->assertSee('رابط المطعم على Google Maps');
});

test('place search results are cached so nominatim is not hammered', function () {
    Cache::flush();
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(nominatimResults())]);

    $service = app(PlaceSearchService::class);

    $service->search('cafe riche cairo');
    $service->search('CAFE RICHE CAIRO');
    $service->search('cafe riche cairo');

    Http::assertSentCount(1);
});

test('a place query is only sent to google once it is specific enough', function () {
    $service = app(GooglePlaceService::class);

    expect($service->placeFromQuery('ab'))->toBeNull();
});

test('a searched coordinate is read back out of the saved google maps link', function () {
    $restaurant = Restaurant::factory()->create([
        'map_latitude' => '30.0469677',
        'map_longitude' => '31.2382388',
        'map_url' => 'https://www.google.com/maps/search/?api=1&query=30.0469677%2C31.2382388',
    ]);

    expect(app(GooglePlaceService::class)->coordinates($restaurant->map_url))
        ->toBe(['lat' => 30.0469677, 'lng' => 31.2382388]);
});
