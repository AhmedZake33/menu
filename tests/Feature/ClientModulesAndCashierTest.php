<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Item;
use App\Models\MenuOrder;
use App\Models\MenuPage;
use App\Models\MenuTheme;
use App\Models\Reservation;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function clientWith(array $attributes = []): array
{
    $restaurant = Restaurant::factory()->create($attributes);
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main', 'is_default' => true]);
    MenuTheme::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id]);
    $category = Category::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'name' => 'Hot', 'slug' => 'hot']);
    $item = Item::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'category_id' => $category->id, 'name' => 'Latte', 'price' => 50]);

    return [$restaurant, $admin, $item, $page];
}

test('a client that was never configured keeps every existing module and has no cashier', function () {
    [$restaurant] = clientWith();

    expect($restaurant->hasModule('menu_pages'))->toBeTrue()
        ->and($restaurant->hasModule('qr'))->toBeTrue()
        ->and($restaurant->hasModule('orders'))->toBeFalse()
        ->and($restaurant->hasModule('cashier'))->toBeFalse()
        ->and($restaurant->hasModule('nonexistent'))->toBeFalse();
});

test('a module switched off disappears from the sidebar and its url is not found', function () {
    [$restaurant, $admin] = clientWith(['modules' => ['categories' => false]]);

    $this->actingAs($admin)->get(route('dashboard.home'))
        ->assertOk()
        ->assertSee('صفحات المنيو')
        ->assertDontSee(route('dashboard.categories.index'))
        ->assertDontSee(route('dashboard.cashier.index'));

    $this->actingAs($admin)->get(route('dashboard.categories.index'))->assertNotFound();
    $this->actingAs($admin)->post(route('dashboard.categories.store'), ['name' => 'x'])->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.cashier.index'))->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.orders.index'))->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.items.index'))->assertOk();
});

test('qr and theme buttons follow their modules', function () {
    [$restaurant, $admin, , $page] = clientWith(['modules' => ['qr' => false, 'theme' => false]]);

    $this->actingAs($admin)->get(route('dashboard.menu-pages.index'))
        ->assertOk()
        ->assertDontSee('QR واحد لكل المنيو')
        ->assertDontSee(route('dashboard.theme.edit', $page));

    $this->actingAs($admin)->get(route('dashboard.restaurant.qr', 'svg'))->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.theme.edit', $page))->assertNotFound();
});

test('switching off the public menu hides it from visitors', function () {
    [$restaurant] = clientWith(['modules' => ['public_menu' => false]]);

    $this->get(route('public.restaurant', $restaurant))->assertNotFound();
});

test('super admin saves modules limits and cashier settings per client', function () {
    [$restaurant] = clientWith();
    $super = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($super)->put(route('admin.restaurants.update', $restaurant), [
        'name' => $restaurant->name,
        'slug' => $restaurant->slug,
        'is_active' => 1,
        'modules' => ['menu_pages' => 1, 'items' => 1, 'cashier' => 1, 'orders' => 1, 'categories' => 0],
        'limits' => ['max_menu_pages' => 1, 'max_items' => 0],
        'cashier' => ['tax_percent' => 14, 'service_percent' => 12, 'allow_discount' => 0, 'payment_methods' => ['cash', 'wallet'], 'receipt_footer' => 'شكرا'],
    ])->assertSessionHasNoErrors();

    $restaurant->refresh();

    expect($restaurant->hasModule('cashier'))->toBeTrue()
        ->and($restaurant->hasModule('orders'))->toBeTrue()
        ->and($restaurant->ordering_enabled)->toBeTrue()
        ->and($restaurant->hasModule('categories'))->toBeFalse()
        ->and($restaurant->hasModule('qr'))->toBeFalse()
        ->and($restaurant->limit('max_menu_pages'))->toBe(1)
        ->and($restaurant->cashierSettings()['allow_discount'])->toBeFalse()
        ->and(array_keys($restaurant->paymentMethods()))->toBe(['cash', 'wallet']);
});

test('menu page limit stops a client from adding more pages', function () {
    [$restaurant, $admin] = clientWith(['settings' => ['limits' => ['max_menu_pages' => 1]]]);

    $this->actingAs($admin)->post(route('dashboard.menu-pages.store'), ['name' => 'Second', 'slug' => 'second'])
        ->assertSessionHasErrors('name');

    expect($restaurant->menuPages()->count())->toBe(1);
});

test('cashier records a paid sale with server side prices service and tax', function () {
    [$restaurant, $admin, $item] = clientWith([
        'modules' => ['cashier' => true],
        'settings' => ['cashier' => ['tax_percent' => 14, 'service_percent' => 10, 'allow_discount' => true, 'payment_methods' => ['cash', 'card']]],
    ]);

    $this->actingAs($admin)->get(route('dashboard.cashier.index'))->assertOk()->assertSee('Latte');

    $this->actingAs($admin)->post(route('dashboard.cashier.sales.store'), [
        'items' => [['id' => $item->id, 'quantity' => 2, 'price' => 1]],
        'order_type' => 'takeaway',
        'discount' => 10,
        'payment_method' => 'card',
    ])->assertRedirect(route('dashboard.cashier.index'))->assertSessionHas('receipt_order');

    $order = MenuOrder::sole();

    // 100 - 10 = 90; service 9; tax 14% of 99 = 13.86; total 112.86
    expect($order->source)->toBe('cashier')
        ->and($order->payment_status)->toBe('paid')
        ->and($order->payment_method)->toBe('card')
        ->and((float) $order->subtotal)->toBe(100.0)
        ->and((float) $order->service)->toBe(9.0)
        ->and((float) $order->tax)->toBe(13.86)
        ->and((float) $order->total)->toBe(112.86)
        ->and($order->cashier_id)->toBe($admin->id);

    $this->actingAs($admin)->get(route('dashboard.cashier.receipt', $order))->assertOk()->assertSee('112.86');
    $this->actingAs($admin)->get(route('dashboard.cashier.report'))->assertOk()->assertSee('112.86');
});

test('cashier ignores a discount when the client is not allowed to give one and rejects foreign items and methods', function () {
    [$restaurant, $admin, $item] = clientWith([
        'modules' => ['cashier' => true],
        'settings' => ['cashier' => ['allow_discount' => false, 'payment_methods' => ['cash']]],
    ]);
    [, , $foreignItem] = clientWith();

    $this->actingAs($admin)->post(route('dashboard.cashier.sales.store'), [
        'items' => [['id' => $item->id, 'quantity' => 1]], 'order_type' => 'dine_in', 'discount' => 20, 'payment_method' => 'cash',
    ])->assertSessionHasNoErrors();

    expect((float) MenuOrder::sole()->total)->toBe(50.0);

    $this->actingAs($admin)->post(route('dashboard.cashier.sales.store'), [
        'items' => [['id' => $foreignItem->id, 'quantity' => 1]], 'order_type' => 'dine_in', 'payment_method' => 'cash',
    ])->assertSessionHasErrors('items');

    $this->actingAs($admin)->post(route('dashboard.cashier.sales.store'), [
        'items' => [['id' => $item->id, 'quantity' => 1]], 'order_type' => 'dine_in', 'payment_method' => 'card',
    ])->assertSessionHasErrors('payment_method');

    expect(MenuOrder::count())->toBe(1);
});

test('cashier collects an unpaid menu order once and never another clients order', function () {
    [$restaurant, $admin] = clientWith(['modules' => ['cashier' => true], 'ordering_enabled' => true]);
    [$other] = clientWith();
    $make = fn (Restaurant $r) => MenuOrder::create(['restaurant_id' => $r->id, 'customer_name' => 'Ali', 'customer_email' => 'a@b.c', 'subtotal' => 80, 'total' => 80, 'confirmation_token' => (string) Str::uuid()]);
    $order = $make($restaurant);
    $foreign = $make($other);

    $this->actingAs($admin)->get(route('dashboard.cashier.index'))->assertOk()->assertSee('في انتظار التحصيل');

    $this->actingAs($admin)->post(route('dashboard.cashier.orders.pay', $order), ['payment_method' => 'cash'])->assertSessionHasNoErrors();
    expect($order->fresh()->payment_status)->toBe('paid');

    $this->actingAs($admin)->post(route('dashboard.cashier.orders.pay', $order), ['payment_method' => 'cash'])->assertSessionHasErrors();
    $this->actingAs($admin)->post(route('dashboard.cashier.orders.pay', $foreign), ['payment_method' => 'cash'])->assertNotFound();
    $this->actingAs($admin)->get(route('dashboard.cashier.receipt', $foreign))->assertNotFound();
    expect($foreign->fresh()->payment_status)->toBe('unpaid');
});

test('client edits its cashier settings but cannot grant itself discounts', function () {
    [$restaurant, $admin] = clientWith(['modules' => ['cashier' => true], 'settings' => ['cashier' => ['allow_discount' => false]]]);

    $this->actingAs($admin)->put(route('dashboard.cashier.settings.update'), [
        'tax_percent' => 14, 'service_percent' => 0, 'payment_methods' => ['cash', 'card'], 'allow_discount' => 1,
    ])->assertSessionHasNoErrors();

    $settings = $restaurant->fresh()->cashierSettings();
    expect($settings['tax_percent'])->toBe(14.0)->and($settings['allow_discount'])->toBeFalse();
});

test('item images are ignored hidden and kept when the client does not have the module', function () {
    Storage::fake('public');
    [$restaurant, $admin, $item, $page] = clientWith(['modules' => ['item_images' => false]]);
    $item->update(['image' => 'items/old.jpg']);
    $file = fn () => UploadedFile::fake()->image('a.jpg');

    $this->actingAs($admin)->get(route('dashboard.items.index'))->assertOk()->assertDontSee('name="image"', false)->assertDontSee('items/old.jpg');

    $this->actingAs($admin)->post(route('dashboard.items.store'), ['category_id' => $item->category_id, 'name' => 'New', 'price' => 10, 'image' => $file()])->assertSessionHasNoErrors();
    expect(Item::where('name', 'New')->sole()->image)->toBeNull();

    $this->actingAs($admin)->put(route('dashboard.items.update', $item), ['category_id' => $item->category_id, 'name' => 'Latte', 'price' => 55, 'is_active' => 1, 'is_available' => 1, 'image' => $file()])->assertSessionHasNoErrors();
    expect($item->fresh()->image)->toBe('items/old.jpg');

    $this->actingAs($admin)->delete(route('dashboard.items.image.destroy', $item))->assertNotFound();
    $this->get(route('public.restaurant', $restaurant))->assertOk()->assertDontSee('items/old.jpg');

    $restaurant->update(['modules' => ['item_images' => true]]);
    $this->get(route('public.restaurant', $restaurant))->assertOk()->assertSee('items/old.jpg');
});

test('online reservations exist only for clients that have the module', function () {
    [$off] = clientWith();
    [$restaurant, $admin] = clientWith(['modules' => ['reservations' => true]]);
    $payload = ['customer_name' => 'Mona', 'customer_phone' => '01000000000', 'date' => now()->addDay()->format('Y-m-d'), 'time' => '20:30', 'guests' => 4];

    $this->get(route('public.restaurant', $off))->assertOk()->assertDontSee('احجز طاولة');
    $this->post(route('public.reservations.store', $off), $payload)->assertNotFound();
    $this->actingAs(User::where('restaurant_id', $off->id)->first())->get(route('dashboard.reservations.index'))->assertNotFound();
    auth()->logout();

    $this->get(route('public.restaurant', $restaurant))->assertOk()->assertSee('احجز طاولة');
    $this->post(route('public.reservations.store', $restaurant), [...$payload, 'date' => now()->subDay()->format('Y-m-d')])->assertSessionHasErrors('time');
    $this->post(route('public.reservations.store', $restaurant), $payload)->assertSessionHasNoErrors();

    $reservation = Reservation::sole();
    expect($reservation->restaurant_id)->toBe($restaurant->id)->and($reservation->guests)->toBe(4)->and($reservation->status)->toBe('pending');

    $this->actingAs($admin)->get(route('dashboard.reservations.index'))->assertOk()->assertSee('Mona');
    $this->actingAs($admin)->patch(route('dashboard.reservations.update', $reservation), ['status' => 'confirmed'])->assertSessionHasNoErrors();
    expect($reservation->fresh()->status)->toBe('confirmed');

    $this->actingAs(User::where('restaurant_id', $off->id)->first())->patch(route('dashboard.reservations.update', $reservation), ['status' => 'cancelled'])->assertNotFound();
});

test('admin gives a client its own dashboard colour and a colour too light is refused', function () {
    [$restaurant, $admin] = clientWith();
    $super = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $base = ['name' => $restaurant->name, 'slug' => $restaurant->slug, 'is_active' => 1];

    $this->actingAs($admin)->get(route('dashboard.home'))->assertSee('--brand:'.config('modules.brand.default'), false);

    $this->actingAs($super)->put(route('admin.restaurants.update', $restaurant), [...$base, 'brand_color' => '#FFF2A8'])->assertSessionHasErrors('brand_color');
    $this->actingAs($super)->put(route('admin.restaurants.update', $restaurant), [...$base, 'brand_color' => '#C2410C'])->assertSessionHasNoErrors();

    expect($restaurant->fresh()->brandColor())->toBe('#c2410c');
    $this->actingAs($admin->fresh())->get(route('dashboard.home'))->assertSee('--brand:#c2410c;--brand-rgb:194,65,12', false);
});
