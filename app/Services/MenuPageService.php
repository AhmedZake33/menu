<?php

namespace App\Services;

use App\Models\MenuPage;
use App\Models\MenuTheme;
use Illuminate\Support\Facades\DB;

class MenuPageService
{
    public function create(int $restaurantId, array $data): MenuPage
    {
        return DB::transaction(function () use ($restaurantId, $data) {
            if ($data['is_default'] ?? false) {
                MenuPage::where('restaurant_id', $restaurantId)->update(['is_default' => false]);
            } $page = MenuPage::create([...$data, 'restaurant_id' => $restaurantId]);
            MenuTheme::create(['restaurant_id' => $restaurantId, 'menu_page_id' => $page->id]);

            return $page;
        });
    }

    public function update(MenuPage $page, array $data): MenuPage
    {
        return DB::transaction(function () use ($page, $data) {
            if ($data['is_default'] ?? false) {
                MenuPage::where('restaurant_id', $page->restaurant_id)->whereKeyNot($page->id)->update(['is_default' => false]);
            }$page->update($data);

            return $page;
        });
    }
}
