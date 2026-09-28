<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class GooglePlaceService
{
    private const PATTERNS = [
        '/(?:placeid|place_id|ftid)=([^&\s]+)/i',
        '/!1s([\w:-]+)/',
        '%/maps/place/([\w:-]+)%',
        '%/maps/place/([^/@?]+)%',
    ];

    public function extract(?string $url): ?string
    {
        return $url && $this->isGoogleHost($url) ? $this->match($url) : null;
    }

    public function resolve(?string $url): ?string
    {
        if (! $url || ! $this->isGoogleHost($url)) {
            return null;
        }

        if ($placeId = $this->match($url)) {
            return $placeId;
        }

        return $this->followRedirect($url);
    }

    private function match(string $url): ?string
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return rawurldecode($matches[1]);
            }
        }

        return null;
    }

    private function isGoogleHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && preg_match('/(^|\.)(google\.[a-z.]+|goo\.gl|maps\.app\.goo\.gl)$/i', $host) === 1;
    }

    private function followRedirect(string $url): ?string
    {
        try {
            $response = Http::withOptions(['allow_redirects' => ['max' => 5, 'strict' => true, 'referer' => false]])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; GoogleReviewsBot/1.0)'])
                ->timeout(5)
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        $final = (string) $response->effectiveUri();

        return $final !== $url && $this->isGoogleHost($final) ? $this->match($final) : null;
    }
}
