<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('panel sidebar is rendered as a mobile offcanvas menu', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::RestaurantAdmin, 'restaurant_id' => $restaurant->id]);

    $this->actingAs($admin)->get(route('dashboard.home'))
        ->assertOk()
        ->assertSee('data-bs-toggle="offcanvas"', false)
        ->assertSee('data-bs-target="#panelSidebar"', false)
        ->assertSee('data-panel-content', false)
        ->assertSee('offcanvas-lg offcanvas-end')
        ->assertSee('صفحات المنيو');
});
