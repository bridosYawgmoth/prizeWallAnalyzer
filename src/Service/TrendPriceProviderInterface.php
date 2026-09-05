<?php

declare(strict_types=1);

namespace App\Service;

interface TrendPriceProviderInterface
{
    /**
     * @return float|null the trend price in EUR, or null when no trend price is available
     */
    public function trendPriceFor(int $productId): ?float;
}
