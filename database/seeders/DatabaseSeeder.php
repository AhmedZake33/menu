<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Item;
use App\Models\MenuPage;
use App\Models\MenuTheme;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            User::updateOrCreate(['email' => 'admin@example.com'], ['name' => 'Super Admin', 'password' => 'password', 'role' => UserRole::SuperAdmin, 'restaurant_id' => null, 'is_active' => true]);
            $restaurant = Restaurant::updateOrCreate(['slug' => 'cafe-mocha'], ['name' => 'Cafe Mocha', 'description' => 'قهوة مختصة وطعام طازج', 'phone' => '01000000000', 'whatsapp' => '201000000000', 'currency' => 'EGP', 'is_active' => true]);
            User::updateOrCreate(['email' => 'restaurant@example.com'], ['name' => 'مدير كافيه موكا', 'password' => 'password', 'role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id, 'is_active' => true]);
            $pages = ['main-menu' => ['المنيو الرئيسية', '#7c3aed'], 'breakfast' => ['الإفطار', '#ea580c'], 'drinks' => ['المشروبات', '#0891b2']];
            foreach ($pages as $pi => $config) {
                $page = MenuPage::updateOrCreate(['restaurant_id' => $restaurant->id, 'slug' => $pi], ['name' => $config[0], 'description' => 'تشكيلة مختارة بعناية', 'sort_order' => array_search($pi, array_keys($pages)), 'is_default' => $pi === 'main-menu', 'is_active' => true]);
                MenuTheme::updateOrCreate(['menu_page_id' => $page->id], ['restaurant_id' => $restaurant->id, 'primary_color' => $config[1], 'background_color' => '#f8f7fc', 'text_color' => '#211d2c', 'price_color' => $config[1]]);
                foreach (['اختيارات الشيف', 'الأكثر طلبًا', 'المميز اليوم'] as $ci => $categoryName) {
                    $category = Category::updateOrCreate(['menu_page_id' => $page->id, 'slug' => "section-$ci"], ['restaurant_id' => $restaurant->id, 'name' => $categoryName, 'sort_order' => $ci, 'is_active' => true]);
                    foreach (range(1, 5) as $ii) {
                        Item::updateOrCreate(['category_id' => $category->id, 'name' => "{$categoryName} {$ii}"], ['restaurant_id' => $restaurant->id, 'menu_page_id' => $page->id, 'price' => 35 + ($ci * 20) + ($ii * 5), 'short_description' => 'مكونات طازجة ومذاق مميز', 'sort_order' => $ii, 'is_available' => $ii !== 5, 'is_active' => true, 'is_featured' => $ii === 1, 'is_new' => $ii === 2]);
                    }
                }
            }
        });
    }
}
