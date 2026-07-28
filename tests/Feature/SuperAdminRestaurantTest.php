<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('super admin can create a cafe with its administrator account', function () {
    $admin = User::factory()->create([
        'role' => UserRole::SuperAdmin,
        'restaurant_id' => null,
    ]);

    $this->actingAs($admin)->post(route('admin.restaurants.store'), [
        'name' => 'New Cafe',
        'slug' => 'new-cafe',
        'email' => 'hello@new-cafe.test',
        'admin_name' => 'Cafe Manager',
        'admin_email' => 'manager@new-cafe.test',
        'password' => 'secure-password',
    ])->assertRedirect(route('admin.restaurants.index'));

    $this->assertDatabaseHas('restaurants', ['slug' => 'new-cafe']);
    $this->assertDatabaseHas('users', [
        'email' => 'manager@new-cafe.test',
        'role' => UserRole::RestaurantAdmin->value,
    ]);
});
