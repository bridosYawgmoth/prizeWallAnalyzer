<?php

namespace App\Adapter;

use App\Dto\PrizeWallItem;

final class FanfinityAdapter implements PrizeWallAdapterInterface
{
    /** @inheritDoc */
    public function fetch(string $url): array
    {
        throw new \RuntimeException('FanfinityAdapter::fetch() is not implemented yet.');
    }
}
