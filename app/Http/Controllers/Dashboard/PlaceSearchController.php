<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\PlaceSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceSearchController extends Controller
{
    public function __construct(private PlaceSearchService $places) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:120'],
        ]);

        $places = $this->places->search($validated['q']);

        return response()->json([
            'places' => $places,
            'message' => $places === []
                ? 'مفيش نتايج للبحث ده. جرّب اسم أطول أو اسم الشارع.'
                : null,
        ]);
    }

    /**
     * A picked candidate is only a coordinate, so the review QR still needs the Google
     * Place ID. This is best effort: the map works either way, the QR waits for a link.
     */
    public function placeId(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:300'],
        ]);

        return response()->json([
            'place_id' => $this->places->placeIdFor($validated['name'], $validated['address'] ?? ''),
        ]);
    }
}
