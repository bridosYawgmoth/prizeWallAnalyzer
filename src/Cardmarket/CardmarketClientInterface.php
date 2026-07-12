<?php

declare(strict_types=1);

namespace App\Cardmarket;

interface CardmarketClientInterface
{
    public function getProduct(int $productId): array;
}
