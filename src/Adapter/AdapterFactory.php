<?php

namespace App\Adapter;

use App\Exception\UnsupportedOrganizerException;

final class AdapterFactory
{
    public function getForUrl(string $url): PrizeWallAdapterInterface
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return match (true) {
            str_contains($host, 'mtgfestivals.com') => new PastimeEventsAdapter(),
            str_contains($host, 'fanfinity.gg')     => new FanfinityAdapter(),

            default => throw new UnsupportedOrganizerException(sprintf('No adapter found for URL: %s', $url)),
        };
    }
}
