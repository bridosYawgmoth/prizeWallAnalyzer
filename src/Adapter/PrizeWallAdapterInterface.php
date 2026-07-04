<?php

namespace App\Adapter;

use App\Dto\PrizeWallItem;

interface PrizeWallAdapterInterface
{
    /**
     * Fetch and parse a prize wall at the given URL.
     *
     * @return PrizeWallItem[]
     */
    public function fetch(string $url): array;
}
