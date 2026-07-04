<?php

namespace App\Adapter;

use App\Dto\PrizeWallItem;

final class PastimeEventsAdapter implements PrizeWallAdapterInterface
{
    /** @inheritDoc */
    public function fetch(string $url): array
    {
        throw new \RuntimeException('PastimeEventsAdapter::fetch() is not implemented yet.');
    }
}
