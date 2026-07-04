<?php

namespace App\Adapter;

final class PastimeEventsAdapter implements PrizeWallAdapterInterface
{
    public function fetch(string $url): array
    {
        throw new \RuntimeException('PastimeEventsAdapter::fetch() is not implemented yet.');
    }
}
