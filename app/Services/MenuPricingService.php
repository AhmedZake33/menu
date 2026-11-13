<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Restaurant;
use Illuminate\Support\Collection;

class MenuPricingService
{
    public function activeOffers(Restaurant $restaurant): Collection
    {
        return $restaurant->hasModule('offers') ? $restaurant->offers()->current()->orderBy('id')->get() : collect();
    }

    /**
     * @param  iterable<Item>  $items
     * @return array<int, array{price: float, original_price: float, offer_title: ?string}>
     */
    public function prices(Restaurant $restaurant, iterable $items, ?Collection $offers = null): array
    {
        $offers ??= $this->activeOffers($restaurant);
        $prices = [];

        foreach ($items as $item) {
            // Existing prices keep their meaning: price is the selling price,
            // old_price is the original price before an individual discount.
            $base = (int) round((float) $item->price * 100);
            $original = max($base, (int) round((float) $item->old_price * 100));
            $best = $base;
            $title = null;

            foreach ($offers as $offer) {
                $discount = $offer->discount_type === 'percent'
                    ? (int) round($original * (float) $offer->discount_value / 100)
                    : (int) round((float) $offer->discount_value * 100);
                $candidate = max(0, $original - $discount);

                // Offers compete with the item's own discount; they never compound.
                if ($candidate < $best) {
                    $best = $candidate;
                    $title = $offer->title;
                }
            }

            $prices[$item->id] = ['price' => $best / 100, 'original_price' => $original / 100, 'offer_title' => $title];
        }

        return $prices;
    }
}
