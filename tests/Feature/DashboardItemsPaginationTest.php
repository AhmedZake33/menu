<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Item;
use App\Models\MenuPage;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('items dashboard renders bootstrap pagination', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main']);
    $category = Category::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'name' => 'Drinks', 'slug' => 'drinks']);

    foreach (range(1, 21) as $index) {
        Item::create([
            'restaurant_id' => $restaurant->id,
            'menu_page_id' => $page->id,
            'category_id' => $category->id,
            'name' => 'Item '.$index,
            'price' => $index,
        ]);
    }

    $this->actingAs($admin)->get(route('dashboard.items.index'))
        ->assertOk()
        ->assertSee('pagination-footer')
        ->assertSee('page-link');
});

test('items dashboard shows edit controls', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main']);
    $category = Category::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'name' => 'Drinks', 'slug' => 'drinks']);
    $item = Item::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'category_id' => $category->id, 'name' => 'Latte', 'price' => 50, 'image' => 'items/latte.jpg']);

    $this->actingAs($admin)->get(route('dashboard.items.index'))
        ->assertOk()
        ->assertSee('تعديل '.$item->name)
        ->assertSee('viewItemImage'.$item->id)
        ->assertSee('صورة '.$item->name)
        ->assertSee(route('dashboard.items.update', $item))
        ->assertSee('حذف الصورة');
});

test('categories dashboard uses the redesigned create and category cards', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main']);
    Category::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'name' => 'Drinks', 'slug' => 'drinks']);

    $this->actingAs($admin)->get(route('dashboard.categories.index'))
        ->assertOk()
        ->assertSee('إضافة تصنيف جديد')
        ->assertSee('category-card')
        ->assertSee('تعديل Drinks');
});

test('restaurant admin can update an item and replace its image', function () {
    Storage::fake('public');

    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main']);
    $category = Category::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'name' => 'Drinks', 'slug' => 'drinks']);
    $item = Item::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'category_id' => $category->id, 'name' => 'Latte', 'price' => 50, 'image' => 'items/old.jpg']);
    Storage::disk('public')->put('items/old.jpg', 'old');

    $this->actingAs($admin)->put(route('dashboard.items.update', $item), [
        'category_id' => $category->id,
        'name' => 'Iced Latte',
        'short_description' => 'Cold coffee',
        'price' => 65,
        'old_price' => 80,
        'sort_order' => 4,
        'is_active' => 1,
        'is_available' => 1,
        'is_featured' => 1,
        'image' => testPngUpload(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $item->refresh();

    expect($item->name)->toBe('Iced Latte')
        ->and($item->price)->toBe('65.00')
        ->and($item->is_featured)->toBeTrue()
        ->and($item->image)->not->toBe('items/old.jpg');

    Storage::disk('public')->assertMissing('items/old.jpg');
    Storage::disk('public')->assertExists($item->image);
});

function testPngUpload(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'item-image');
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

    return new UploadedFile($path, 'latte.png', 'image/png', null, true);
}

test('restaurant admin can delete only the item image', function () {
    Storage::fake('public');

    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main']);
    $category = Category::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'name' => 'Drinks', 'slug' => 'drinks']);
    $item = Item::create(['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'category_id' => $category->id, 'name' => 'Latte', 'price' => 50, 'image' => 'items/latte.jpg']);
    Storage::disk('public')->put('items/latte.jpg', 'image');

    $this->actingAs($admin)->delete(route('dashboard.items.image.destroy', $item))
        ->assertRedirect();

    expect($item->fresh()->image)->toBeNull();
    Storage::disk('public')->assertMissing('items/latte.jpg');
});
