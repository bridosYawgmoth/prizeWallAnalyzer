<?php

declare(strict_types=1);

namespace App\Service;

use App\Cardmarket\CardmarketClientInterface;

final class CardmarketTrendPriceProvider implements TrendPriceProviderInterface
{
    public function __construct(
        private readonly CardmarketClientInterface $cardmarketClient,
    ) {
    }

    /** @inheritDoc */
    public function trendPriceFor(int $productId): ?float
    {
        $response = $this->cardmarketClient->getProduct(productId: $productId);

        if (!isset($response['product']['priceGuide']['TREND'])) {
            return null;
        }

        return (float) $response['product']['priceGuide']['TREND'];
    }
}
