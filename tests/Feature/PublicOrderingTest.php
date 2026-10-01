<?php

use App\Enums\UserRole;
use App\Events\MenuOrderCreated;
use App\Mail\MenuOrderConfirmationMail;
use App\Mail\MenuOrderVerificationCodeMail;
use App\Models\Category;
use App\Models\Item;
use App\Models\MenuPage;
use App\Models\MenuTheme;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * @return array{0: Restaurant, 1: Item}
 */
function orderableCafe(int $tablesCount = 6): array
{
    $restaurant = Restaurant::create([
        'name' => 'Cafe',
        'slug' => 'cafe',
        'ordering_enabled' => true,
        'tables_count' => $tablesCount,
        'currency' => 'EGP',
    ]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main', 'is_default' => true, 'is_active' => true]);
    MenuTheme::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id]);
    $category = Category::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'name' => 'Drinks', 'slug' => 'drinks', 'is_active' => true]);
    $item = Item::create([
        'restaurant_id' => $restaurant->id,
        'menu_page_id' => $page->id,
        'category_id' => $category->id,
        'name' => 'Latte',
        'price' => 120,
        'is_active' => true,
        'is_available' => true,
    ]);

    return [$restaurant, $item];
}

function placeOrder(TestCase $test, Restaurant $restaurant, Item $item, array $details = []): int
{
    $response = $test->postJson(route('public.orders.code', $restaurant), array_merge([
        'customer_name' => 'Mona',
        'customer_email' => 'mona@example.com',
        'items' => [['id' => $item->id, 'quantity' => 1]],
    ], $details))->assertOk();

    return $test->postJson(route('public.orders.confirm', $restaurant), [
        'verification_token' => $response->json('token'),
        'verification_code' => Mail::sent(MenuOrderVerificationCodeMail::class)->first()->code,
    ])->assertOk()->json('order_id');
}

it('requires email code verification before creating an order', function () {
    Mail::fake();

    [$restaurant, $item] = orderableCafe();

    $response = $this->postJson(route('public.orders.code', $restaurant), [
        'customer_name' => 'Ahmed',
        'customer_email' => 'ahmed@example.com',
        'table_number' => 3,
        'items' => [
            ['id' => $item->id, 'quantity' => 2],
        ],
    ])->assertOk()->assertJsonStructure(['message', 'token']);

    $this->assertDatabaseMissing('menu_orders', [
        'restaurant_id' => $restaurant->id,
        'customer_email' => 'ahmed@example.com',
    ]);

    Mail::assertSent(MenuOrderVerificationCodeMail::class);

    $verificationMail = Mail::sent(MenuOrderVerificationCodeMail::class)->first();
    Event::fake([MenuOrderCreated::class]);

    $this->postJson(route('public.orders.confirm', $restaurant), [
        'verification_token' => $response->json('token'),
        'verification_code' => $verificationMail->code,
    ])->assertOk()->assertJsonPath('order_id', 1);

    $this->assertDatabaseHas('menu_orders', [
        'restaurant_id' => $restaurant->id,
        'customer_email' => 'ahmed@example.com',
        'table_number' => 3,
        'total' => 240,
    ]);

    Mail::assertSent(MenuOrderConfirmationMail::class);
    Event::assertDispatched(MenuOrderCreated::class);
});

it('accepts an online order with no table number', function () {
    Mail::fake();
    Event::fake([MenuOrderCreated::class]);

    [$restaurant, $item] = orderableCafe();

    placeOrder($this, $restaurant, $item, ['table_number' => null]);

    $this->assertDatabaseHas('menu_orders', [
        'restaurant_id' => $restaurant->id,
        'customer_email' => 'mona@example.com',
        'table_number' => null,
        'total' => 120,
    ]);

    Event::assertDispatched(MenuOrderCreated::class);
});

it('accepts an online order when the table picker is left on its empty choice', function () {
    Mail::fake();

    [$restaurant, $item] = orderableCafe();

    // This is what the browser actually posts when the customer keeps the default option.
    placeOrder($this, $restaurant, $item, ['table_number' => '']);

    $this->assertDatabaseHas('menu_orders', [
        'restaurant_id' => $restaurant->id,
        'customer_email' => 'mona@example.com',
        'table_number' => null,
    ]);
});

it('accepts an online order from a cafe that never set up any tables', function () {
    Mail::fake();

    [$restaurant, $item] = orderableCafe(0);

    placeOrder($this, $restaurant, $item);

    $this->assertDatabaseHas('menu_orders', [
        'restaurant_id' => $restaurant->id,
        'customer_email' => 'mona@example.com',
        'table_number' => null,
        'total' => 120,
    ]);
});

it('offers ordering without a table picker when no tables are configured', function () {
    Mail::fake();

    [$restaurant] = orderableCafe(0);

    $this->get(route('public.restaurant', $restaurant))
        ->assertOk()
        ->assertSee('data-order-add', false)
        ->assertDontSee('name="table_number"', false);
});

it('offers the table picker as an optional choice when tables are configured', function () {
    Mail::fake();

    [$restaurant] = orderableCafe(6);

    $this->get(route('public.restaurant', $restaurant))
        ->assertOk()
        ->assertSee('name="table_number"', false)
        // optional: not required, and "no table" is the default choice
        ->assertDontSee('name="table_number" required', false)
        ->assertSee('بدون رقم طاولة');
});

it('still rejects a table number outside the configured range', function () {
    Mail::fake();

    [$restaurant, $item] = orderableCafe(6);

    $this->postJson(route('public.orders.code', $restaurant), [
        'customer_name' => 'Ahmed',
        'customer_email' => 'ahmed@example.com',
        'table_number' => 9,
        'items' => [['id' => $item->id, 'quantity' => 1]],
    ])->assertStatus(422)->assertJsonValidationErrors('table_number');
});

it('shows an order without a table in the dashboard and leaves it off the email', function () {
    Mail::fake();

    [$restaurant, $item] = orderableCafe();
    placeOrder($this, $restaurant, $item);

    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)
        ->get(route('dashboard.orders.index'))
        ->assertOk()
        ->assertSee('Mona')
        ->assertSee('—', false);

    Mail::assertSent(MenuOrderConfirmationMail::class, fn (MenuOrderConfirmationMail $mail) => ! str_contains($mail->render(), 'رقم الطاولة'));
});
