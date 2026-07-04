<?php

namespace App\Dto;

final class PrizeWallItem
{
    public ?float $eurPrice = null;

    public function __construct(
        public readonly string $name,
        public readonly int $tixPrice,
    ) {
    }
}
