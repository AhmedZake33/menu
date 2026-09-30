<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class GooglePlaceService
{
    private const PLACE_ID_PATTERNS = [
        '/(?:placeid|place_id)=([A-Za-z0-9_-]{20,})/i',
        '/!1s([A-Za-z0-9_-]{20,})(?!:)/',
        '%/maps/place/([A-Za-z0-9_-]{20,})%',
    ];

    private const FEATURE_ID_PATTERNS = [
        '/!1s(0x[\da-f]+:0x[\da-f]+)/i',
        '/(?:ftid|placeid|place_id)=(0x[\da-f]+:0x[\da-f]+)/i',
    ];

    /**
     * The keyless Maps embed endpoint answers a place query with a bootstrap call to
     * initEmbed([...]). When the query identifies a single place the array carries
     * ["0x…:0x…","Name, Address",[lat,lng]] right next to its "ChIJ…" Place ID. Queries
     * that Google treats as a category search instead come back as a list of anonymous
     * candidates, and those match nothing here, so they fall through to a null result.
     */
    private const EMBED_PLACE_PATTERN = '/\["(?<fid>0x[\da-f]+:0x[\da-f]+)","(?<full>(?:[^"\\\\]|\\\\.)*)",\[(?<lat>-?\d+(?:\.\d+)?),(?<lng>-?\d+(?:\.\d+)?)\]/i';

    private const EMBED_PLACE_ID_PATTERN = '/"(?<pid>ChIJ[A-Za-z0-9_-]{15,})"/';

    private const EMBED_NAME_PATTERN = '/\],"(?<name>(?:[^"\\\\]|\\\\.)*)"/';

    public function extract(?string $url): ?string
    {
        if (! $url || ! $this->isGoogleHost($url)) {
            return null;
        }

        foreach (self::PLACE_ID_PATTERNS as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return rawurldecode($matches[1]);
            }
        }

        return $this->featureId($url);
    }

    public function featureId(?string $url): ?string
    {
        if (! $url || ! $this->isGoogleHost($url)) {
            return null;
        }

        foreach (self::FEATURE_ID_PATTERNS as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return strtolower($matches[1]);
            }
        }

        return null;
    }

    public function isFeatureId(?string $value): bool
    {
        return is_string($value) && preg_match('/^0x[\da-f]+:0x[\da-f]+$/i', $value) === 1;
    }

    public function cidFromFeatureId(?string $featureId): ?string
    {
        if (! $this->isFeatureId($featureId)) {
            return null;
        }

        [, $second] = explode(':', $featureId, 2);

        return $this->hexToDecimal($this->stripPrefix($second)) ?: null;
    }

    /**
     * A Place ID (ChIJ...) and a feature id (0x..:0x..) are the same identifier in two
     * encodings: the feature id pair packed into a fixed 20 byte protobuf, base64url encoded.
     * Reverse engineered and undocumented, but it is what lets us build an official
     * writereview link from a plain Maps URL without a Places API key.
     */
    public function placeIdFromFeatureId(?string $featureId): ?string
    {
        if (! $this->isFeatureId($featureId)) {
            return null;
        }

        [$first, $second] = explode(':', $featureId, 2);

        $bytes = array_merge(
            [0x0A, 0x12, 0x09],
            $this->hexToLittleEndianBytes($this->stripPrefix($first)),
            [0x11],
            $this->hexToLittleEndianBytes($this->stripPrefix($second)),
        );

        return rtrim(strtr(base64_encode(implode('', array_map('chr', $bytes))), '+/', '-_'), '=');
    }

    private function stripPrefix(string $hex): string
    {
        return preg_replace('/^0x/i', '', trim($hex)) ?: '';
    }

    private function hexToLittleEndianBytes(string $hex): array
    {
        $hex = str_pad(strtolower($hex), 16, '0', STR_PAD_LEFT);
        $bytes = [];

        for ($i = 14; $i >= 0; $i -= 2) {
            $bytes[] = (int) hexdec(substr($hex, $i, 2));
        }

        return $bytes;
    }

    public function resolve(?string $url): ?string
    {
        if (! $url || ! $this->isGoogleHost($url)) {
            return null;
        }

        if ($id = $this->extract($url)) {
            return $id;
        }

        return $this->followRedirect($url);
    }

    /**
     * Latitude/longitude carried inside a shared Maps link, so the location can be
     * shown on a keyless embed without the Maps JavaScript API.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function coordinates(?string $url): ?array
    {
        if (! $url) {
            return null;
        }

        $patterns = [
            '/!3d(-?[\d.]+)!4d(-?[\d.]+)/',
            '/@(-?[\d.]+),(-?[\d.]+)/',
            '/[?&](?:q|query|ll)=(-?[\d.]+),(-?[\d.]+)/',
            '/[?&]center=(-?[\d.]+),(-?[\d.]+)/',
        ];

        // A pair is routinely written as %2C, and a saved map link is the only record
        // left of a location that was picked from a search rather than pasted.
        $url = str_ireplace('%2c', ',', $url);

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $url, $matches)) {
                continue;
            }

            $lat = (float) $matches[1];
            $lng = (float) $matches[2];

            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return ['lat' => round($lat, 7), 'lng' => round($lng, 7)];
            }
        }

        return null;
    }

    /**
     * Turn a place name typed by a person into the place Google resolved it to,
     * without a Places API key, so a searched location can fill in the same fields
     * a pasted link does.
     *
     * @return array{place_id: string, name: string, address: string, lat: float, lng: float}|null
     */
    public function placeFromQuery(string $query): ?array
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return null;
        }

        $html = $this->fetch('https://www.google.com/maps?q='.rawurlencode($query).'&output=embed');

        if (! $html || ! preg_match(self::EMBED_PLACE_PATTERN, $html, $anchor)) {
            return null;
        }

        if (! preg_match(self::EMBED_PLACE_ID_PATTERN, $html, $place)) {
            return null;
        }

        $full = $this->unescape($anchor['full']);
        $name = $this->unescape($this->valueAfter($html, $anchor[0], self::EMBED_NAME_PATTERN) ?? $full);
        $address = str_starts_with($full, $name) ? ltrim(trim(substr($full, strlen($name))), ',') : $full;

        return [
            'place_id' => $place['pid'],
            'name' => $name,
            'address' => trim($address, ' ,'),
            'lat' => round((float) $anchor['lat'], 7),
            'lng' => round((float) $anchor['lng'], 7),
        ];
    }

    private function fetch(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; GooglePlaceResolver/1.0)',
                'Accept-Language' => 'en',
            ])
                ->timeout(6)
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    /**
     * The clean place name sits just past the coordinate pair, but only when the entry
     * has no trailing id, so it is looked up from that offset rather than by position.
     */
    private function valueAfter(string $html, string $anchor, string $pattern): ?string
    {
        $rest = substr($html, (int) strpos($html, $anchor) + strlen($anchor));

        return preg_match($pattern, $rest, $match) ? $match['name'] : null;
    }

    private function unescape(string $value): string
    {
        $decoded = preg_replace_callback(
            '/\\\\u([0-9a-f]{4})/i',
            fn (array $match) => mb_chr(hexdec($match[1]), 'UTF-8'),
            $value
        );

        return str_replace(['\\/', '\\"', '\\\\'], ['/', '"', '\\'], $decoded ?? $value);
    }

    private function isGoogleHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && preg_match('/(^|\.)(google\.[a-z.]+|goo\.gl)$/i', $host) === 1;
    }

    private function followRedirect(string $url): ?string
    {
        try {
            $response = Http::withOptions(['allow_redirects' => ['max' => 5, 'strict' => true, 'referer' => false]])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; GooglePlaceResolver/1.0)'])
                ->timeout(5)
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        $final = (string) $response->effectiveUri();

        return $final !== $url && $this->isGoogleHost($final) ? $this->extract($final) : null;
    }

    private function hexToDecimal(string $hex): ?string
    {
        $hex = ltrim(strtolower($hex), '0');

        if ($hex === '') {
            return '0';
        }

        $decimal = '0';

        foreach (str_split($hex) as $char) {
            $digit = strpos('0123456789abcdef', $char);

            if ($digit === false) {
                return null;
            }

            $decimal = $this->multiplyAdd($decimal, 16, $digit);
        }

        return $decimal;
    }

    private function multiplyAdd(string $number, int $multiplier, int $addend): string
    {
        $carry = $addend;
        $result = '';

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $value = ((int) $number[$i]) * $multiplier + $carry;
            $result = ($value % 10).$result;
            $carry = intdiv($value, 10);
        }

        while ($carry > 0) {
            $result = ($carry % 10).$result;
            $carry = intdiv($carry, 10);
        }

        return $result === '' ? '0' : $result;
    }
}
