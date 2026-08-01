<?php

declare(strict_types=1);

namespace App\Cardmarket;

interface CardmarketClientInterface
{
    /**
     * Returns the decoded Cardmarket payload, or an empty array when the
     * request fails. Callers must interpret the payload rather than catch.
     */
    public function getProduct(int $productId): array;
}
