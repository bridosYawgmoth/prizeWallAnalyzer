<?php

declare(strict_types=1);

namespace App\Dto;

final class PriceRetrievalResult
{
    /**
     * @param PrizeWallItem[] $prizeWallItems
     * @param PrizeWallItem[] $prizeWallItemsNotFound
     */
    public function __construct(
        public readonly array $prizeWallItems,
        public readonly array $prizeWallItemsNotFound,
    ) {
    }
}
