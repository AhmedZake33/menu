<?php

use App\Mail\MenuOrderConfirmationMail;
use App\Models\Category;
use App\Models\Item;
use App\Models\MenuPage;
use App\Models\MenuTheme;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Mail;

it('allows customers to create an order when restaurant ordering is enabled', function () {
    Mail::fake();

    $restaurant = Restaurant::create([
        'name' => 'Cafe',
        'slug' => 'cafe',
        'ordering_enabled' => true,
        'tables_count' => 6,
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

    $this->post(route('public.orders.store', $restaurant), [
        'customer_name' => 'Ahmed',
        'customer_email' => 'ahmed@example.com',
        'table_number' => 3,
        'items' => [
            ['id' => $item->id, 'quantity' => 2],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('menu_orders', [
        'restaurant_id' => $restaurant->id,
        'customer_email' => 'ahmed@example.com',
        'table_number' => 3,
        'total' => 240,
    ]);

    Mail::assertSent(MenuOrderConfirmationMail::class);
});
