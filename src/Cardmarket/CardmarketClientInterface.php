<?php

declare(strict_types=1);

namespace App\Cardmarket;

interface CardmarketClientInterface
{
    public function get(string $url): array;
}
