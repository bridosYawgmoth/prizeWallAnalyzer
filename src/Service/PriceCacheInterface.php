<?php

declare(strict_types=1);

namespace App\Service;

interface PriceCacheInterface
{
    /**
     * @param string[] $names
     * @return array<string, float> cached prices keyed by the requested name, omitting names without a cached price
     */
    public function pricesFor(string $organizer, array $names): array;
}
