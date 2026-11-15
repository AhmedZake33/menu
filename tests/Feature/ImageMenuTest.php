<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Item;
use App\Models\MenuImage;
use App\Models\MenuPage;
use App\Models\MenuTheme;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function imageClient(array $attributes = []): array
{
    $restaurant = Restaurant::factory()->create($attributes);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    return [$restaurant, $admin];
}

function digitalClient(array $attributes = []): array
{
    [$restaurant, $admin] = imageClient($attributes);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main', 'is_default' => true]);
    MenuTheme::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id]);
    $category = Category::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'name' => 'Hot', 'slug' => 'hot']);
    Item::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'category_id' => $category->id, 'name' => 'Latte', 'price' => 50]);

    return [$restaurant, $admin, $page];
}

function pic(string $name = 'menu.jpg', int $kb = 100): UploadedFile
{
    return UploadedFile::fake()->image($name, 600, 800)->size($kb);
}

test('every existing client stays on the digital menu', function () {
    [$restaurant] = digitalClient();
    $restaurant = $restaurant->fresh();

    expect($restaurant->menu_mode)->toBe('digital')
        ->and($restaurant->usesImageMenu())->toBeFalse()
        ->and($restaurant->hasModule('items'))->toBeTrue();
    $this->get(route('public.restaurant', $restaurant))->assertOk()->assertSee('Latte');
});

test('a client chooses the image menu and the digital sections disappear without losing data', function () {
    [$restaurant, $admin] = digitalClient(['modules' => ['orders' => true, 'cashier' => true, 'reservations' => true], 'ordering_enabled' => true]);

    $this->actingAs($admin)->put(route('dashboard.menu-mode.update'), ['menu_mode' => 'images'])
        ->assertRedirect(route('dashboard.menu-images.index'));

    expect($restaurant->fresh()->usesImageMenu())->toBeTrue();

    foreach (['menu_pages', 'categories', 'items', 'theme', 'offers', 'orders', 'reservations', 'cashier'] as $module) {
        expect($restaurant->fresh()->hasModule($module))->toBeFalse();
    }

    $this->actingAs($admin)->get(route('dashboard.items.index'))->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.categories.index'))->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.menu-pages.index'))->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.cashier.index'))->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.orders.index'))->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.reservations.index'))->assertNotFound();

    $this->actingAs($admin)->get(route('dashboard.home'))->assertOk()
        ->assertSee(route('dashboard.menu-images.index'))
        ->assertDontSee(route('dashboard.items.index'));

    // Everything is still there for the day they switch back.
    expect(Item::where('restaurant_id', $restaurant->id)->count())->toBe(1);
    $this->actingAs($admin)->put(route('dashboard.menu-mode.update'), ['menu_mode' => 'digital'])->assertRedirect();
    $this->actingAs($admin)->get(route('dashboard.items.index'))->assertOk()->assertSee('Latte');
    expect($restaurant->fresh()->hasModule('cashier'))->toBeTrue();
});

test('the admin form keeps the digital sections the admin switched on while a client is on the image menu', function () {
    [$restaurant] = digitalClient(['menu_mode' => 'images', 'modules' => ['items' => true, 'cashier' => true]]);
    $super = User::factory()->create(['role' => UserRole::SuperAdmin, 'restaurant_id' => null]);

    expect($restaurant->moduleEnabled('items'))->toBeTrue()->and($restaurant->hasModule('items'))->toBeFalse();
    $this->actingAs($super)->get(route('admin.restaurants.edit', $restaurant))->assertOk()
        ->assertSee('نوع المنيو')
        ->assertSee('id="module-items"', false);

    $html = $this->actingAs($super)->get(route('admin.restaurants.edit', $restaurant))->getContent();
    expect($html)->toMatch('/id="module-items"[^>]*checked/')->toMatch('/id="module-cashier"[^>]*checked/');
});

test('the invalid menu type is refused', function () {
    [, $admin] = imageClient();

    $this->actingAs($admin)->put(route('dashboard.menu-mode.update'), ['menu_mode' => 'other'])->assertSessionHasErrors('menu_mode');
});

test('a client whose image menu option is switched off cannot use it', function () {
    [$restaurant, $admin] = imageClient(['modules' => ['image_menu' => false], 'menu_mode' => 'images']);

    expect($restaurant->usesImageMenu())->toBeFalse();
    $this->actingAs($admin)->get(route('dashboard.menu-mode.edit'))->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.menu-images.index'))->assertNotFound();
    $this->actingAs($admin)->put(route('dashboard.menu-mode.update'), ['menu_mode' => 'images'])->assertNotFound();
});

test('a client uploads menu pictures and customers see them on the link, in order', function () {
    Storage::fake('public');
    [$restaurant, $admin] = imageClient(['menu_mode' => 'images', 'name' => 'Pic Cafe']);

    $this->actingAs($admin)->post(route('dashboard.menu-images.store'), ['images' => [pic('a.jpg'), pic('b.png'), pic('c.webp')]])
        ->assertSessionHasNoErrors();

    $images = $restaurant->menuImages()->get();
    expect($images)->toHaveCount(3)->and($images->pluck('sort_order')->all())->toBe([1, 2, 3]);
    $images->each(fn ($image) => Storage::disk('public')->assertExists($image->path));

    $html = $this->get(route('public.restaurant', $restaurant))->assertOk()->assertSee('Pic Cafe')->getContent();
    foreach ($images as $image) {
        expect($html)->toContain($image->url());
    }
    expect(strpos($html, $images[0]->url()))->toBeLessThan(strpos($html, $images[2]->url()));

    // The short link and the QR target are the same address.
    $this->get(route('public.short', $restaurant))->assertOk()->assertSee($images[0]->url());
    $this->actingAs($admin)->get(route('dashboard.restaurant.qr', 'svg'))->assertOk();
    $this->assertDatabaseHas('menu_views', ['restaurant_id' => $restaurant->id, 'menu_page_id' => null]);
});

test('uploads outside the allowed picture types are refused', function (UploadedFile $file) {
    Storage::fake('public');
    [$restaurant, $admin] = imageClient(['menu_mode' => 'images']);

    $this->actingAs($admin)->post(route('dashboard.menu-images.store'), ['images' => [$file]])->assertSessionHasErrors('images.0');
    expect($restaurant->menuImages()->count())->toBe(0);
})->with([
    'pdf' => fn () => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf'),
    'script' => fn () => UploadedFile::fake()->create('shell.php', 10, 'text/x-php'),
    'svg' => fn () => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'too big' => fn () => UploadedFile::fake()->image('huge.jpg')->size(9000),
]);

test('nothing is uploaded without a file, and at most 20 at a time', function () {
    Storage::fake('public');
    [$restaurant, $admin] = imageClient(['menu_mode' => 'images']);

    $this->actingAs($admin)->post(route('dashboard.menu-images.store'), [])->assertSessionHasErrors('images');
    $this->actingAs($admin)->post(route('dashboard.menu-images.store'), ['images' => array_map(fn () => pic(), range(1, 21))])->assertSessionHasErrors('images');
    expect($restaurant->menuImages()->count())->toBe(0);
});

test('the picture limit set for the client is enforced', function () {
    Storage::fake('public');
    [$restaurant, $admin] = imageClient(['menu_mode' => 'images', 'settings' => ['limits' => ['max_menu_images' => 2]]]);

    $this->actingAs($admin)->post(route('dashboard.menu-images.store'), ['images' => [pic(), pic()]])->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('dashboard.menu-images.store'), ['images' => [pic()]])->assertSessionHasErrors('images');
    expect($restaurant->menuImages()->count())->toBe(2);
});

test('pictures can be reordered and deleted, and the file goes with them', function () {
    Storage::fake('public');
    [$restaurant, $admin] = imageClient(['menu_mode' => 'images']);
    $this->actingAs($admin)->post(route('dashboard.menu-images.store'), ['images' => [pic('a.jpg'), pic('b.jpg'), pic('c.jpg')]]);
    [$first, $second, $third] = $restaurant->menuImages()->get()->all();

    $this->actingAs($admin)->patch(route('dashboard.menu-images.move', $third), ['direction' => 'up'])->assertRedirect();
    expect($restaurant->menuImages()->pluck('id')->all())->toBe([$first->id, $third->id, $second->id]);

    // The first cannot go further up and the last cannot go further down.
    $this->actingAs($admin)->patch(route('dashboard.menu-images.move', $first), ['direction' => 'up']);
    $this->actingAs($admin)->patch(route('dashboard.menu-images.move', $second), ['direction' => 'down']);
    expect($restaurant->menuImages()->pluck('id')->all())->toBe([$first->id, $third->id, $second->id]);

    $this->actingAs($admin)->delete(route('dashboard.menu-images.destroy', $third))->assertRedirect();
    expect($restaurant->menuImages()->pluck('id')->all())->toBe([$first->id, $second->id]);
    Storage::disk('public')->assertMissing($third->path);
});

test('one client cannot see, move or delete another client\'s pictures', function () {
    Storage::fake('public');
    [$mine, $me] = imageClient(['menu_mode' => 'images']);
    [$other, $them] = imageClient(['menu_mode' => 'images']);
    $this->actingAs($them)->post(route('dashboard.menu-images.store'), ['images' => [pic()]]);
    $theirs = $other->menuImages()->first();

    $this->actingAs($me)->get(route('dashboard.menu-images.index'))->assertOk()->assertDontSee($theirs->url());
    $this->actingAs($me)->delete(route('dashboard.menu-images.destroy', $theirs))->assertNotFound();
    $this->actingAs($me)->patch(route('dashboard.menu-images.move', $theirs), ['direction' => 'down'])->assertNotFound();
    expect(MenuImage::count())->toBe(1);

    // And their public page never shows mine.
    $this->get(route('public.restaurant', $mine))->assertOk()->assertDontSee($theirs->url());
});

test('picture uploads work even when the general files module is off, but other uploads still do not', function () {
    Storage::fake('public');
    [$restaurant, $admin] = imageClient(['menu_mode' => 'images', 'modules' => ['files' => false]]);

    $this->actingAs($admin)->post(route('dashboard.menu-images.store'), ['images' => [pic()]])->assertSessionHasNoErrors();
    expect($restaurant->menuImages()->count())->toBe(1);

    $this->actingAs($admin)->put(route('dashboard.restaurant-settings.update'), [
        'name' => $restaurant->name, 'slug' => $restaurant->slug, 'currency' => 'EGP', 'logo' => UploadedFile::fake()->image('logo.png'),
    ])->assertForbidden();
});

test('an old digital page link lands on the picture menu', function () {
    [$restaurant, , $page] = digitalClient(['menu_mode' => 'images']);

    $this->get(route('public.menu', [$restaurant, $page]))->assertRedirect(route('public.restaurant', $restaurant));
});

test('the picture menu shows a friendly message while there are no pictures yet', function () {
    [$restaurant] = imageClient(['menu_mode' => 'images']);

    $this->get(route('public.restaurant', $restaurant))->assertOk()->assertSee('المنيو قيد التحديث');
});

test('an expired or switched-off client shows no picture menu', function () {
    Storage::fake('public');
    [$expired, $admin] = imageClient(['menu_mode' => 'images']);
    $this->actingAs($admin)->post(route('dashboard.menu-images.store'), ['images' => [pic()]]);

    $expired->update(['expires_at' => now()->subMinute()]);
    $this->get(route('public.restaurant', $expired))->assertNotFound();

    [$hidden] = imageClient(['menu_mode' => 'images', 'modules' => ['public_menu' => false]]);
    $this->get(route('public.restaurant', $hidden))->assertNotFound();
});

test('ordering endpoints stay closed for a client on the image menu', function () {
    [$restaurant] = digitalClient(['menu_mode' => 'images', 'ordering_enabled' => true]);

    $this->post(route('public.orders.code', $restaurant), [])->assertNotFound();
    $this->post(route('public.reservations.store', $restaurant), [])->assertNotFound();
});

test('the super admin can put a client on either menu type', function () {
    [$restaurant] = imageClient();
    $super = User::factory()->create(['role' => UserRole::SuperAdmin, 'restaurant_id' => null]);
    $base = ['name' => $restaurant->name, 'slug' => $restaurant->slug, 'is_active' => 1];

    $this->actingAs($super)->put(route('admin.restaurants.update', $restaurant), [...$base, 'menu_mode' => 'images'])->assertSessionHasNoErrors();
    expect($restaurant->fresh()->menu_mode)->toBe('images');

    $this->actingAs($super)->put(route('admin.restaurants.update', $restaurant), [...$base, 'menu_mode' => 'nope'])->assertSessionHasErrors('menu_mode');
    $this->actingAs($super)->put(route('admin.restaurants.update', $restaurant), $base)->assertSessionHasNoErrors();
    expect($restaurant->fresh()->menu_mode)->toBe('images');
});

test('the super admin sees which clients are on trial and what they chose', function () {
    $super = User::factory()->create(['role' => UserRole::SuperAdmin, 'restaurant_id' => null]);
    $this->post('/register', signup_for_admin_view());
    auth()->logout();

    $this->actingAs($super)->get(route('admin.restaurants.index'))->assertOk()->assertSee('تجربة')->assertSee('منيو بالصور');
    $this->actingAs($super)->get(route('admin.dashboard'))->assertOk()->assertSee('تجارب مجانية جارية');
    $restaurant = Restaurant::firstWhere('slug', 'trial-cafe');
    $this->actingAs($super)->get(route('admin.restaurants.show', $restaurant))->assertOk()
        ->assertSee('Trial Manager')->assertSee('trial@example.com')->assertSee('فترة تجريبية');
});

function signup_for_admin_view(): array
{
    return [
        'restaurant_name' => 'Trial Cafe', 'slug' => 'trial-cafe', 'phone' => '0100', 'menu_mode' => 'images',
        'name' => 'Trial Manager', 'email' => 'trial@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
    ];
}
