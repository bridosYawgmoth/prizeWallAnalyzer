<?php

namespace App\Adapter;

final class FanfinityAdapter implements PrizeWallAdapterInterface
{
    public function fetch(string $url): array
    {
        throw new \RuntimeException('FanfinityAdapter::fetch() is not implemented yet.');
    }
}
