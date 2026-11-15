<?php

use App\Enums\UserRole;
use App\Models\MenuPage;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function signup(array $overrides = []): array
{
    return [
        'restaurant_name' => 'Cafe Mocha',
        'slug' => 'cafe-mocha',
        'phone' => '01012345678',
        'menu_mode' => 'digital',
        'name' => 'Ahmed Manager',
        'email' => 'ahmed@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        ...$overrides,
    ];
}

test('sign-up can be switched off', function () {
    config(['modules.signup.enabled' => false]);

    $this->get('/register')->assertNotFound();
    $this->post('/register', signup())->assertNotFound();
    expect(Restaurant::count())->toBe(0);
});

test('visitors see the sign-up form and the links to it', function () {
    $this->get('/register')->assertOk()->assertSee('سجّل مطعمك أو الكافيه')->assertSee('30 يوم');
    $this->get(route('login'))->assertSee(route('register'));
    $this->get(route('public.features'))->assertSee(route('register'));
});

test('an owner signs up and gets a restaurant, a manager account and a month of free use', function () {
    $this->travelTo(now()->startOfSecond());

    $this->post('/register', signup())->assertRedirect(route('dashboard.home'));

    $restaurant = Restaurant::where('slug', 'cafe-mocha')->firstOrFail();
    $user = User::where('email', 'ahmed@example.com')->firstOrFail();

    expect($restaurant->name)->toBe('Cafe Mocha')
        ->and($restaurant->phone)->toBe('01012345678')
        ->and($restaurant->is_active)->toBeTrue()
        ->and($restaurant->isTrial())->toBeTrue()
        ->and($restaurant->expires_at->equalTo(now()->addDays(30)))->toBeTrue()
        ->and($restaurant->daysLeft())->toBe(30)
        ->and($restaurant->isAvailable())->toBeTrue()
        ->and($restaurant->menu_mode)->toBe('digital')
        ->and($restaurant->limit('max_menu_images'))->toBe(5)
        ->and($user->role)->toBe(UserRole::RestaurantAdmin)
        ->and($user->restaurant_id)->toBe($restaurant->id)
        ->and($user->name)->toBe('Ahmed Manager')
        ->and($user->is_active)->toBeTrue();

    // Logged in straight away, with a first menu page so the public link already works.
    $this->assertAuthenticatedAs($user);
    expect(MenuPage::where('restaurant_id', $restaurant->id)->where('is_default', true)->count())->toBe(1);
    $this->get(route('dashboard.home'))->assertOk()->assertSee('الفترة التجريبية');
    $this->get(route('public.restaurant', $restaurant))->assertOk();
    $this->assertDatabaseHas('activity_logs', ['action' => 'restaurant.registered', 'restaurant_id' => $restaurant->id]);
});

test('the trial length comes from the configuration', function () {
    config(['modules.signup.trial_days' => 14]);

    $this->post('/register', signup())->assertRedirect();

    expect(Restaurant::firstWhere('slug', 'cafe-mocha')->daysLeft())->toBe(14);
});

test('the trial ends on its own and then closes the dashboard and the public link like any expired client', function () {
    $this->post('/register', signup())->assertRedirect();
    $restaurant = Restaurant::firstWhere('slug', 'cafe-mocha');

    $this->travel(31)->days();

    $this->get(route('dashboard.home'))->assertForbidden();
    $this->get(route('public.restaurant', $restaurant))->assertNotFound();
});

test('a new account only sees its own restaurant', function () {
    $other = Restaurant::factory()->create();
    $this->post('/register', signup())->assertRedirect();

    $mine = Restaurant::firstWhere('slug', 'cafe-mocha');

    expect(auth()->user()->restaurant_id)->toBe($mine->id)->not->toBe($other->id);
    $this->get(route('admin.dashboard'))->assertForbidden();
});

test('the link and the email must be free, and the link must be clean', function (array $overrides, string $field) {
    Restaurant::factory()->create(['slug' => 'taken']);
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post('/register', signup($overrides))->assertSessionHasErrors($field);
    expect(Restaurant::where('slug', 'cafe-mocha')->exists())->toBeFalse();
    $this->assertGuest();
})->with([
    'link taken' => [['slug' => 'taken'], 'slug'],
    'link taken in other letters' => [['slug' => 'TAKEN'], 'slug'],
    'arabic link' => [['slug' => 'مقهى'], 'slug'],
    'spaces in link' => [['slug' => 'my cafe'], 'slug'],
    'short link' => [['slug' => 'ab'], 'slug'],
    'email taken' => [['email' => 'taken@example.com'], 'email'],
    'weak password' => [['password' => 'abc', 'password_confirmation' => 'abc'], 'password'],
    'password mismatch' => [['password_confirmation' => 'different'], 'password'],
    'no phone' => [['phone' => ''], 'phone'],
    'bad menu type' => [['menu_mode' => 'hologram'], 'menu_mode'],
]);

test('an upper-case link is saved in lower case', function () {
    $this->post('/register', signup(['slug' => 'Cafe-Mocha']))->assertSessionHasNoErrors();

    expect(Restaurant::where('slug', 'cafe-mocha')->exists())->toBeTrue();
});

test('a bot that fills the hidden field creates nothing', function () {
    $this->post('/register', signup(['company_website' => 'http://spam.example']))->assertRedirect(route('public.features'));

    expect(Restaurant::count())->toBe(0)->and(User::count())->toBe(0);
    $this->assertGuest();
});

test('one address cannot open accounts without limit', function () {
    foreach (range(1, 6) as $i) {
        $this->post('/register', signup(['slug' => "cafe-$i", 'email' => "owner$i@example.com"]));
        auth()->logout();
    }

    $this->post('/register', signup(['slug' => 'cafe-7', 'email' => 'owner7@example.com']))->assertStatus(429);
    expect(Restaurant::count())->toBe(6);
});

test('a signed-in user is sent away from the sign-up form', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/register')->assertRedirect();
});

test('a trial restaurant can choose the image menu while signing up', function () {
    $this->post('/register', signup(['menu_mode' => 'images']))->assertRedirect();

    $restaurant = Restaurant::firstWhere('slug', 'cafe-mocha');
    expect($restaurant->menu_mode)->toBe('images')->and($restaurant->usesImageMenu())->toBeTrue();
});

test('a new account on the image menu can upload five pictures and no more', function () {
    Storage::fake('public');
    $pic = fn () => UploadedFile::fake()->image('p.jpg', 600, 800);

    $this->get('/register')->assertSee('ترفع حتى 5 صور');

    $this->post('/register', signup(['menu_mode' => 'images']))->assertRedirect();
    $restaurant = Restaurant::firstWhere('slug', 'cafe-mocha');

    $this->post(route('dashboard.menu-images.store'), ['images' => [$pic(), $pic(), $pic(), $pic(), $pic()]])->assertSessionHasNoErrors();
    expect($restaurant->menuImages()->count())->toBe(5);

    $this->post(route('dashboard.menu-images.store'), ['images' => [$pic()]])->assertSessionHasErrors('images');
    expect($restaurant->menuImages()->count())->toBe(5);
});

test('the admin raises or removes the picture limit for a client and the client can then upload more', function () {
    Storage::fake('public');
    $pic = fn () => UploadedFile::fake()->image('p.jpg', 600, 800);

    $this->post('/register', signup(['menu_mode' => 'images']))->assertRedirect();
    $restaurant = Restaurant::firstWhere('slug', 'cafe-mocha');
    $owner = User::firstWhere('email', 'ahmed@example.com');
    $this->post(route('dashboard.menu-images.store'), ['images' => [$pic(), $pic(), $pic(), $pic(), $pic()]]);
    auth()->logout();

    $super = User::factory()->create(['role' => UserRole::SuperAdmin, 'restaurant_id' => null]);
    $base = ['name' => $restaurant->name, 'slug' => $restaurant->slug, 'is_active' => 1];

    // The admin form shows the current limit...
    $this->actingAs($super)->get(route('admin.restaurants.edit', $restaurant))->assertOk()
        ->assertSee('أقصى عدد لصور المنيو')->assertSee('name="limits[max_menu_images]"', false)->assertSee('value="5"', false);

    // ...raising it lets the client upload more...
    $this->actingAs($super)->put(route('admin.restaurants.update', $restaurant), [...$base, 'limits' => ['max_menu_images' => 8]])->assertSessionHasNoErrors();
    expect($restaurant->fresh()->limit('max_menu_images'))->toBe(8);
    $this->actingAs($owner)->post(route('dashboard.menu-images.store'), ['images' => [$pic(), $pic(), $pic()]])->assertSessionHasNoErrors();
    expect($restaurant->menuImages()->count())->toBe(8);
    $this->actingAs($owner)->post(route('dashboard.menu-images.store'), ['images' => [$pic()]])->assertSessionHasErrors('images');

    // ...and 0 means unlimited.
    $this->actingAs($super)->put(route('admin.restaurants.update', $restaurant), [...$base, 'limits' => ['max_menu_images' => 0]])->assertSessionHasNoErrors();
    $owner = $owner->fresh(); // a real request loads the restaurant again; this object still holds the old limit
    $this->actingAs($owner)->post(route('dashboard.menu-images.store'), ['images' => [$pic(), $pic()]])->assertSessionHasNoErrors();
    expect($restaurant->menuImages()->count())->toBe(10);
});
