<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Item;
use App\Models\MenuPage;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('restaurant admin cannot edit another tenants menu page', function () {
    $first = Restaurant::create(['name' => 'First', 'slug' => 'first']);
    $second = Restaurant::create(['name' => 'Second', 'slug' => 'second']);
    $admin = User::factory()->create(['restaurant_id' => $first->id, 'role' => UserRole::RestaurantAdmin]);
    $foreignPage = MenuPage::create(['restaurant_id' => $second->id, 'name' => 'Foreign', 'slug' => 'foreign']);

    $this->actingAs($admin)->get(route('dashboard.menu-pages.edit', $foreignPage))->assertForbidden();
});

test('item category validation is scoped to authenticated tenant', function () {
    $first = Restaurant::create(['name' => 'First', 'slug' => 'first']);
    $second = Restaurant::create(['name' => 'Second', 'slug' => 'second']);
    $admin = User::factory()->create(['restaurant_id' => $first->id, 'role' => UserRole::RestaurantAdmin]);
    $page = MenuPage::create(['restaurant_id' => $second->id, 'name' => 'Foreign', 'slug' => 'foreign']);
    $category = Category::create(['restaurant_id' => $second->id, 'menu_page_id' => $page->id, 'name' => 'Foreign', 'slug' => 'foreign']);

    $this->actingAs($admin)->post(route('dashboard.items.store'), [
        'category_id' => $category->id, 'name' => 'Injected', 'price' => 10,
    ])->assertSessionHasErrors('category_id');
});

test('inactive restaurant is hidden publicly', function () {
    $restaurant = Restaurant::create(['name' => 'Closed', 'slug' => 'closed', 'is_active' => false]);

    $this->get(route('public.restaurant', $restaurant))->assertNotFound();
});

test('active public menu renders and records a view', function () {
    $restaurant = Restaurant::create(['name' => 'Cafe', 'slug' => 'cafe']);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main', 'is_active' => true]);
    $page->theme()->create(['restaurant_id' => $restaurant->id]);

    $this->get(route('public.menu', [$restaurant, $page]))
        ->assertOk()
        ->assertSee('Cafe');

    $this->assertDatabaseHas('menu_views', ['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id]);
});

test('restaurant short url renders the default menu without redirecting', function () {
    $restaurant = Restaurant::create(['name' => 'Cafe Mocha', 'slug' => 'cafe-mocha']);
    $page = MenuPage::create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Main Menu',
        'slug' => 'main-menu',
        'is_active' => true,
        'is_default' => true,
    ]);
    $page->theme()->create(['restaurant_id' => $restaurant->id]);

    $this->get('/r/cafe-mocha')
        ->assertOk()
        ->assertSee('Main Menu')
        ->assertSee('Cafe Mocha');
});

test('public menu shows cafe contact links and other menu page links', function () {
    $restaurant = Restaurant::create([
        'name' => 'Cafe Mocha',
        'slug' => 'cafe-mocha',
        'phone' => '01000000000',
        'whatsapp' => '201000000000',
        'website_url' => 'https://cafe.test',
        'instagram_url' => 'https://instagram.com/cafe',
        'address' => 'Cairo',
    ]);
    $main = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main Menu', 'slug' => 'main-menu', 'is_active' => true, 'is_default' => true]);
    $desserts = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Desserts', 'slug' => 'desserts', 'is_active' => true]);
    $main->theme()->create(['restaurant_id' => $restaurant->id]);

    $this->get('/r/cafe-mocha')
        ->assertOk()
        ->assertSee('tel:01000000000')
        ->assertSee('https://wa.me/201000000000')
        ->assertSee('https://cafe.test')
        ->assertSee('data-public-menu-link', false)
        ->assertSee(route('public.menu', [$restaurant, $desserts]))
        ->assertSee('Desserts');
});

test('public menu shows a dynamic map and directions link', function () {
    $restaurant = Restaurant::create([
        'name' => 'Cafe Mocha',
        'slug' => 'cafe-mocha',
        'address' => 'Nasr City Cairo',
    ]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main Menu', 'slug' => 'main-menu', 'is_active' => true, 'is_default' => true]);
    $page->theme()->create(['restaurant_id' => $restaurant->id]);

    $this->get('/r/cafe-mocha')
        ->assertOk()
        ->assertSee('data-public-map-toggle', false)
        ->assertSee('data-public-map-panel', false)
        ->assertSee('class="public-map-card mb-4 d-none"', false)
        ->assertSee('https://www.google.com/maps?q=Nasr%20City%20Cairo&amp;output=embed', false)
        ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=Nasr%20City%20Cairo', false)
        ->assertSee('اذهب للمكان');
});

test('public menu uses saved map coordinates before address', function () {
    $restaurant = Restaurant::create([
        'name' => 'Cafe Mocha',
        'slug' => 'cafe-mocha',
        'address' => 'Nasr City Cairo',
        'map_latitude' => '30.0444200',
        'map_longitude' => '31.2357100',
    ]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main Menu', 'slug' => 'main-menu', 'is_active' => true, 'is_default' => true]);
    $page->theme()->create(['restaurant_id' => $restaurant->id]);

    $this->get('/r/cafe-mocha')
        ->assertOk()
        ->assertSee('https://www.google.com/maps?q=30.0444200%2C31.2357100&amp;output=embed', false)
        ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=30.0444200%2C31.2357100', false);
});

test('public menu renders item images with separate preview modal', function () {
    $restaurant = Restaurant::create(['name' => 'Cafe Mocha', 'slug' => 'cafe-mocha']);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main Menu', 'slug' => 'main-menu', 'is_active' => true, 'is_default' => true]);
    $page->theme()->create(['restaurant_id' => $restaurant->id, 'show_item_images' => true]);
    $category = Category::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'name' => 'Desserts', 'slug' => 'desserts']);
    $item = Item::create([
        'restaurant_id' => $restaurant->id,
        'menu_page_id' => $page->id,
        'category_id' => $category->id,
        'name' => 'Cake',
        'short_description' => 'Chocolate slice',
        'price' => 90,
        'image' => 'items/cake.jpg',
    ]);

    $this->get('/r/cafe-mocha')
        ->assertOk()
        ->assertSee(Storage::url($item->image))
        ->assertSee('publicItemImage'.$item->id)
        ->assertSee('عرض صورة '.$item->name)
        ->assertSee('Chocolate slice')
        ->assertSee('90.00')
        ->assertSee('Desserts');
});

test('qr endpoint contains an svg image for the tenant page', function () {
    $restaurant = Restaurant::create(['name' => 'Cafe', 'slug' => 'cafe']);
    $admin = User::factory()->create(['restaurant_id' => $restaurant->id, 'role' => UserRole::RestaurantAdmin]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main']);

    $this->actingAs($admin)->get(route('dashboard.qr', [$page, 'svg']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('<svg', false);
});

test('restaurant admin can get one qr code for the full restaurant menu', function () {
    $restaurant = Restaurant::create(['name' => 'Cafe', 'slug' => 'cafe']);
    $admin = User::factory()->create(['restaurant_id' => $restaurant->id, 'role' => UserRole::RestaurantAdmin]);

    $this->actingAs($admin)->get(route('dashboard.restaurant.qr', 'svg'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('<svg', false);
});

test('menu pages dashboard shows the full menu qr card', function () {
    $restaurant = Restaurant::create(['name' => 'Cafe', 'slug' => 'cafe']);
    $admin = User::factory()->create(['restaurant_id' => $restaurant->id, 'role' => UserRole::RestaurantAdmin]);
    MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main', 'is_default' => true]);

    $this->actingAs($admin)->get(route('dashboard.menu-pages.index'))
        ->assertOk()
        ->assertSee('QR واحد لكل المنيو')
        ->assertSee(route('public.restaurant', $restaurant));
});
