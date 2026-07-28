<?php

use App\Enums\UserRole;
use App\Models\MenuPage;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('restaurant admin can customize menu colors and styles', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'name' => 'Main', 'slug' => 'main']);
    $page->theme()->create(['restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->put(route('dashboard.theme.update', $page), [
        'primary_color' => '#123456', 'secondary_color' => '#654321', 'background_color' => '#fefefe', 'card_background_color' => '#ffffff',
        'text_color' => '#222222', 'heading_color' => '#111111', 'price_color' => '#cc0000', 'button_color' => '#123456', 'border_color' => '#dddddd',
        'font_family' => 'Cairo', 'heading_font_family' => 'Tajawal', 'layout_type' => 'grid', 'category_layout' => 'pills',
        'item_card_style' => 'horizontal', 'image_position' => 'right', 'image_shape' => 'rounded', 'items_per_row_desktop' => 3,
        'items_per_row_tablet' => 2, 'items_per_row_mobile' => 1, 'card_border_radius' => 18, 'card_shadow' => 'medium', 'content_width' => '1140px',
        'show_item_images' => 1, 'show_descriptions' => 1, 'show_prices' => 1, 'enable_search' => 1,
    ])->assertSessionHasNoErrors();

    expect($page->theme->fresh()->primary_color)->toBe('#123456')
        ->and($page->theme->fresh()->item_card_style)->toBe('horizontal')
        ->and($page->theme->fresh()->font_family)->toBe('Cairo');
});
