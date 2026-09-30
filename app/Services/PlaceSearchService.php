<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class PlaceSearchService
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    private const MIN_QUERY_LENGTH = 3;

    /**
     * Candidates a person can pick from, so a chain or a neighbourhood returns the
     * branch the restaurant is actually in instead of one confident guess.
     *
     * @return array<int, array{label: string, name: string, address: string, lat: float, lng: float}>
     */
    public function search(string $query, int $limit = 6): array
    {
        $query = trim($query);
        $limit = max(1, min($limit, 10));

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        // Nominatim asks for at most one request a second and allows caching, so an
        // identical query from any restaurant admin is only ever sent once a day.
        return Cache::remember(
            'place-search:'.sha1(mb_strtolower($query).':'.$limit),
            now()->addDay(),
            fn () => $this->query($query, $limit)
        );
    }

    /**
     * A searched candidate carries coordinates but no Google Place ID, which is what the
     * review QR needs. Re-resolving the chosen name and address through Google recovers
     * it when Google recognises the place, and quietly returns nothing when it does not.
     */
    public function placeIdFor(string $name, string $address = ''): ?string
    {
        $query = trim($name.' '.$address);

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return null;
        }

        return Cache::remember(
            'place-search:id:'.sha1(mb_strtolower($query)),
            now()->addWeek(),
            fn () => app(GooglePlaceService::class)->placeFromQuery($query)['place_id'] ?? null
        );
    }

    /**
     * @return array<int, array{label: string, name: string, address: string, lat: float, lng: float}>
     */
    private function query(string $query, int $limit): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => trim(config('app.name').' (restaurant location picker)'),
                'Accept-Language' => 'en',
            ])
                ->timeout(6)
                ->retry(1, 200)
                ->get(self::ENDPOINT, [
                    'format' => 'jsonv2',
                    'q' => $query,
                    'limit' => $limit,
                    'addressdetails' => 1,
                ]);
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful() || ! is_array($rows = $response->json())) {
            return [];
        }

        return collect($rows)
            ->filter(fn ($row) => is_array($row) && isset($row['lat'], $row['lon'], $row['display_name']))
            ->map(fn (array $row) => $this->candidate($row))
            ->unique(fn (array $row) => $row['lat'].','.$row['lng'])
            ->values()
            ->all();
    }

    /**
     * @return array{label: string, name: string, address: string, lat: float, lng: float}
     */
    private function candidate(array $row): array
    {
        $label = trim((string) $row['display_name']);
        $name = $this->name($row, $label);

        return [
            'label' => $label,
            'name' => $name,
            'address' => $this->address($row, $label, $name),
            'lat' => round((float) $row['lat'], 7),
            'lng' => round((float) $row['lon'], 7),
        ];
    }

    private function name(array $row, string $label): string
    {
        $name = $row['name'] ?? $row['namedetails']['name'] ?? '';

        return is_string($name) && trim($name) !== '' ? trim($name) : trim(explode(',', $label)[0]);
    }

    private function address(array $row, string $label, string $name): string
    {
        if ($name !== '' && str_starts_with($label, $name)) {
            $rest = trim(substr($label, strlen($name)), ' ,');

            if ($rest !== '') {
                return $rest;
            }
        }

        return $label;
    }
}
