<?php

use App\Enums\UserRole;
use App\Events\MenuOrderCreated;
use App\Mail\MenuOrderVerificationCodeMail;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\MenuOrder;
use App\Models\MenuPage;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\MenuPricingService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->travelTo(now()->startOfSecond());
    $this->restaurant = Restaurant::factory()->create(['ordering_enabled' => true, 'modules' => ['cashier' => true]]);
    $this->manager = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => UserRole::RestaurantAdmin]);
    $this->page = MenuPage::create(['restaurant_id' => $this->restaurant->id, 'name' => 'Menu', 'slug' => 'menu', 'is_default' => true]);
    $this->page->theme()->create(['restaurant_id' => $this->restaurant->id]);
    $this->category = Category::create(['restaurant_id' => $this->restaurant->id, 'menu_page_id' => $this->page->id, 'name' => 'Drinks', 'slug' => 'drinks']);
    $this->item = Item::create([
        'restaurant_id' => $this->restaurant->id, 'menu_page_id' => $this->page->id,
        'category_id' => $this->category->id, 'name' => 'Offer Latte', 'price' => 90, 'old_price' => 100,
    ]);
    $this->offerData = ['title' => 'Weekend offer', 'discount_type' => 'percent', 'discount_value' => 20, 'is_active' => true];
});

test('client can create edit stop and delete its general offers with audit records', function () {
    $this->actingAs($this->manager)->get(route('dashboard.offers.create'))->assertOk();
    $this->post(route('dashboard.offers.store'), $this->offerData + ['restaurant_id' => 999999])
        ->assertRedirect(route('dashboard.offers.index'))->assertSessionHasNoErrors();
    $offer = $this->restaurant->offers()->sole();
    expect($offer->discount_value)->toBe('20.00');
    $this->get(route('dashboard.offers.index'))->assertOk()->assertSee('Weekend offer');
    $this->get(route('dashboard.offers.edit', $offer))->assertOk()->assertSee('Weekend offer');
    $this->put(route('dashboard.offers.update', $offer), array_replace($this->offerData, ['is_active' => false]))
        ->assertRedirect(route('dashboard.offers.index'))->assertSessionHasNoErrors();
    expect($offer->fresh()->is_active)->toBeFalse();
    $this->delete(route('dashboard.offers.destroy', $offer))->assertRedirect();
    expect($this->restaurant->offers()->count())->toBe(0)
        ->and(ActivityLog::where('subject_type', $offer::class)->pluck('action')->all())
        ->toBe(['offer.created', 'offer.updated', 'offer.deleted']);
});

test('offer inputs reject invalid discounts and date ranges', function (array $changes, string $field) {
    $this->actingAs($this->manager)->post(route('dashboard.offers.store'), array_replace($this->offerData, $changes))
        ->assertSessionHasErrors($field);
    expect($this->restaurant->offers()->count())->toBe(0);
})->with([
    [['discount_value' => 101], 'discount_value'],
    [['discount_value' => 0], 'discount_value'],
    [['discount_value' => -1], 'discount_value'],
    [['discount_value' => '1.234'], 'discount_value'],
    [['discount_type' => 'unknown'], 'discount_type'],
    [['starts_at' => '2026-12-02 10:00:00', 'ends_at' => '2026-12-01 10:00:00'], 'ends_at'],
]);

test('clients cannot read change or delete another restaurants offer', function () {
    $other = Restaurant::factory()->create();
    $offer = $other->offers()->create($this->offerData);
    $this->actingAs($this->manager)->get(route('dashboard.offers.index'))->assertOk()->assertDontSee('Weekend offer');
    $this->get(route('dashboard.offers.edit', $offer))->assertForbidden();
    $this->put(route('dashboard.offers.update', $offer), $this->offerData)->assertForbidden();
    $this->delete(route('dashboard.offers.destroy', $offer))->assertForbidden();
    expect($offer->fresh())->not->toBeNull();
});

test('offers require an authorized active client and enabled module', function () {
    $this->get(route('dashboard.offers.index'))->assertRedirect(route('login'));
    $super = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($super)->get(route('dashboard.offers.index'))->assertForbidden();
    $this->restaurant->update(['modules' => ['offers' => false]]);
    $this->actingAs($this->manager)->get(route('dashboard.offers.index'))->assertNotFound();
    $this->post(route('dashboard.offers.store'), $this->offerData)->assertNotFound();
    $this->get(route('dashboard.home'))->assertDontSee(route('dashboard.offers.index'));
});

test('the best offer competes with individual discounts against the original price without stacking', function () {
    $this->restaurant->offers()->create($this->offerData);
    $this->restaurant->offers()->create(array_replace($this->offerData, ['title' => 'Fixed offer', 'discount_type' => 'fixed', 'discount_value' => 25]));
    $pricing = app(MenuPricingService::class)->prices($this->restaurant, [$this->item])[$this->item->id];
    expect($pricing)->toBe(['price' => 75, 'original_price' => 100, 'offer_title' => 'Fixed offer']);

    $this->item->update(['price' => 60]);
    $pricing = app(MenuPricingService::class)->prices($this->restaurant, [$this->item])[$this->item->id];
    expect((float) $pricing['price'])->toBe(60.0)->and($pricing['offer_title'])->toBeNull();
});

test('a fixed offer cannot reduce a price below zero and prices round per unit to cents', function () {
    $offer = $this->restaurant->offers()->create(array_replace($this->offerData, ['discount_type' => 'fixed', 'discount_value' => 200]));
    expect(app(MenuPricingService::class)->prices($this->restaurant, [$this->item])[$this->item->id]['price'])->toEqual(0);
    $offer->update(['discount_type' => 'percent', 'discount_value' => '33.33']);
    $this->item->update(['price' => '10.01', 'old_price' => null]);
    expect(app(MenuPricingService::class)->prices($this->restaurant, [$this->item])[$this->item->id]['price'])->toEqual(6.67);
});

test('future expired stopped foreign and disabled module offers do not affect prices', function () {
    foreach ([['starts_at' => now()->addMinute()], ['ends_at' => now()->subMinute()], ['is_active' => false]] as $changes) {
        $this->restaurant->offers()->create(array_replace($this->offerData, $changes));
    }
    Restaurant::factory()->create()->offers()->create($this->offerData);
    $pricing = app(MenuPricingService::class);
    expect($pricing->prices($this->restaurant, [$this->item])[$this->item->id]['price'])->toEqual(90);
    $this->restaurant->offers()->create($this->offerData);
    $this->restaurant->update(['modules' => ['offers' => false]]);
    expect($pricing->prices($this->restaurant, [$this->item])[$this->item->id]['price'])->toEqual(90);
});

test('a scheduled offer starts and ends automatically', function () {
    $this->restaurant->offers()->create($this->offerData + ['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);
    $pricing = app(MenuPricingService::class);
    expect($pricing->prices($this->restaurant, [$this->item])[$this->item->id]['price'])->toEqual(90);
    $this->travel(1)->hours();
    expect($pricing->prices($this->restaurant, [$this->item])[$this->item->id]['price'])->toEqual(80);
    $this->travel(61)->minutes();
    expect($pricing->prices($this->restaurant, [$this->item])[$this->item->id]['price'])->toEqual(90);
});

test('discounted prices appear in public menu item preview cart and cashier', function () {
    $this->restaurant->offers()->create($this->offerData + ['description' => 'Twenty percent on original prices']);
    $this->item->update(['image' => 'items/latte.jpg']);
    $this->get(route('public.restaurant', $this->restaurant))->assertOk()
        ->assertSee(['Weekend offer', 'Twenty percent on original prices', '80.00 EGP', '100.00 EGP'])
        ->assertSee('data-price="80.00"', false)->assertSee('<del', false);
    $this->actingAs($this->manager)->get(route('dashboard.cashier.index'))->assertOk()
        ->assertSee('Weekend offer')->assertSee('data-price="80.00"', false);
    $this->get(route('dashboard.items.index'))->assertOk()->assertSee(['80.00 EGP', 'السعر الأصلي قبل الخصم']);
});

test('clients can create individual discounts and cannot set an original price below the sale price', function () {
    $data = ['category_id' => $this->category->id, 'name' => 'Discounted Tea', 'price' => 80, 'old_price' => 100, 'is_active' => true, 'is_available' => true];
    $this->actingAs($this->manager)->post(route('dashboard.items.store'), $data)->assertSessionHasNoErrors();
    $item = $this->restaurant->items()->where('name', 'Discounted Tea')->sole();
    expect($item->price)->toBe('80.00')->and($item->old_price)->toBe('100.00');
    $this->put(route('dashboard.items.update', $item), array_replace($data, ['old_price' => 70]))->assertSessionHasErrors('old_price');
    $this->post(route('dashboard.items.store'), array_replace($data, ['old_price' => 70]))->assertSessionHasErrors('old_price');
});

test('cashier uses server offer prices with taxes and manual discount permission and preserves past sales', function () {
    $offer = $this->restaurant->offers()->create($this->offerData);
    $this->restaurant->update(['settings' => ['cashier' => ['tax_percent' => 10, 'service_percent' => 10, 'allow_discount' => false]]]);
    $this->actingAs($this->manager)->post(route('dashboard.cashier.sales.store'), [
        'items' => [['id' => $this->item->id, 'quantity' => 2, 'price' => 1]],
        'order_type' => 'takeaway', 'payment_method' => 'cash', 'discount' => 10,
    ])->assertSessionHasNoErrors();
    $order = MenuOrder::sole();
    expect((float) $order->subtotal)->toBe(160.0)->and((float) $order->discount)->toBe(0.0)
        ->and((float) $order->total)->toBe(193.6)->and((float) $order->items()->sole()->unit_price)->toBe(80.0);
    $offer->delete();
    expect((float) $order->fresh()->total)->toBe(193.6)->and((float) $order->items()->sole()->unit_price)->toBe(80.0);
});

test('online order confirmation stores the server offer price and collection keeps that price', function () {
    Mail::fake();
    Event::fake([MenuOrderCreated::class]);
    $offer = $this->restaurant->offers()->create($this->offerData);
    $response = $this->postJson(route('public.orders.code', $this->restaurant), [
        'customer_name' => 'Mona', 'customer_email' => 'mona@example.test',
        'items' => [['id' => $this->item->id, 'quantity' => 2, 'price' => 1]],
    ])->assertOk()->assertJsonPath('prices.'.$this->item->id, 80);
    $this->postJson(route('public.orders.confirm', $this->restaurant), [
        'verification_token' => $response->json('token'),
        'verification_code' => Mail::sent(MenuOrderVerificationCodeMail::class)->first()->code,
    ])->assertOk();
    $order = MenuOrder::sole();
    expect((float) $order->total)->toBe(160.0)->and((float) $order->items()->sole()->unit_price)->toBe(80.0);
    $offer->delete();
    $this->actingAs($this->manager)->post(route('dashboard.cashier.orders.pay', $order), ['payment_method' => 'cash'])->assertSessionHasNoErrors();
    expect((float) $order->fresh()->total)->toBe(160.0);
});

test('an offer expiring after the verification code requires a fresh quote before placing an order', function () {
    Mail::fake();
    $this->restaurant->offers()->create($this->offerData + ['ends_at' => now()->addMinute()]);
    $response = $this->postJson(route('public.orders.code', $this->restaurant), [
        'customer_name' => 'Mona', 'customer_email' => 'mona@example.test',
        'items' => [['id' => $this->item->id, 'quantity' => 1]],
    ])->assertOk();
    $this->travel(2)->minutes();
    $this->postJson(route('public.orders.confirm', $this->restaurant), [
        'verification_token' => $response->json('token'),
        'verification_code' => Mail::sent(MenuOrderVerificationCodeMail::class)->first()->code,
    ])->assertStatus(409)->assertJsonPath('code', 'prices_changed')->assertJsonPath('prices.'.$this->item->id, 90);
    expect(MenuOrder::count())->toBe(0);
});
