<?php

declare(strict_types=1);

namespace App\Service;

use App\Cardmarket\CardmarketClientInterface;

class PriceTrendRetriever
{
    public function __construct(
        private readonly CardmarketClientInterface $cardmarketClient,
    ) {
    }
}
