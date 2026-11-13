<?php

use App\Models\MenuPage;
use App\Models\Restaurant;

test('visitors can view product features without signing in or creating a restaurant', function () {
    $this->get(route('public.features'))->assertOk()->assertSee([
        'من أول مسحة QR.', 'عروض تشجّع على الاختيار', 'حجوزات مرتّبة', 'تقارير التحصيل اليومية',
        'معاينة توضيحية', 'الأصناف والأسعار للعرض التوضيحي فقط.', route('login'), route('public.demo'),
    ])->assertSee('dir="rtl"', false);
});

test('demo opens only the configured available restaurant and keeps the menu route direct', function () {
    $restaurant = Restaurant::factory()->create(['slug' => 'configured-demo']);
    config(['marketing.demo_restaurant_slug' => 'configured-demo']);
    $page = MenuPage::create(['restaurant_id' => $restaurant->id, 'slug' => 'menu', 'name' => 'Direct Demo Menu', 'is_default' => true]);
    $page->theme()->create(['restaurant_id' => $restaurant->id]);

    $this->get(route('public.demo'))->assertRedirect(route('public.restaurant', $restaurant));
    $this->get(route('public.restaurant', $restaurant))->assertOk()->assertSee('Direct Demo Menu')->assertDontSee('من أول مسحة QR.');
});

test('unavailable demos fall back to the illustrative preview without exposing another tenant', function (array $changes, bool $withMenu) {
    config(['marketing.demo_restaurant_slug' => 'configured-demo']);
    $restaurant = Restaurant::factory()->create(['slug' => 'configured-demo', ...$changes]);
    if ($withMenu) {
        MenuPage::create(['restaurant_id' => $restaurant->id, 'slug' => 'menu', 'name' => 'Demo']);
    }
    $other = Restaurant::factory()->create();
    MenuPage::create(['restaurant_id' => $other->id, 'slug' => 'menu', 'name' => 'Other Client']);

    $this->get(route('public.demo'))->assertRedirect(route('public.features').'#menu-preview')
        ->assertSessionHas('demo_unavailable', true);
})->with([
    [['is_active' => false], true],
    [['expires_at' => '2020-01-01'], true],
    [['modules' => ['public_menu' => false]], true],
    [[], false],
]);

test('missing configured demo never falls back to a random restaurant', function () {
    config(['marketing.demo_restaurant_slug' => 'missing-demo']);
    Restaurant::factory()->create();
    $this->get(route('public.demo'))->assertRedirect(route('public.features').'#menu-preview');
});
