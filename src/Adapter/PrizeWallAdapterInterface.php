<?php

namespace App\Adapter;

interface PrizeWallAdapterInterface
{
    /**
     * Fetch and parse a prize wall at the given URL.
     * Returns raw structured data — shape will be defined when a DTO is introduced.
     */
    public function fetch(string $url): array;
}
