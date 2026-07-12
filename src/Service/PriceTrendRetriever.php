<?php

declare(strict_types=1);

namespace App\Service;

use App\Cardmarket\CardmarketClientInterface;
use App\Dto\PrizeWallItem;
use App\Infrastructure\FileReaderRepositoryInterface;
use App\Infrastructure\JsonParserInterface;

class PriceTrendRetriever
{
    public function __construct(
        private readonly CardmarketClientInterface $cardmarketClient,
        private readonly FileReaderRepositoryInterface $fileReader,
        private readonly JsonParserInterface $jsonParser,
        private readonly string $cacheDir,
    ) {
    }

    /**
     * @param PrizeWallItem[] $items
     * @return PrizeWallItem[]
     */
    /**
     * @param PrizeWallItem[] $items
     * @return PrizeWallItem[]
     */
    public function getPrices(string $organizer, array $items): array
    {
        $cache = $this->readCache($organizer);

        foreach ($items as $item) {
            if ($this->isInCache(name: $item->name, cache: $cache)) {
                $item->eurPrice = $this->readFromCache(name: $item->name, cache: $cache);
            }
        }

        return $items;
    }

    private function readFromCache(string $name, array $cache): float
    {
        return (float) $cache[$this->normalizeName($name)];
    }

    private function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }

    private function isInCache(string $name, array $cache): bool
    {
        return isset($cache[$this->normalizeName($name)]);
    }

    private function readCache(string $organizer): array
    {
        $path = sprintf('%s/%s.json', $this->cacheDir, $organizer);

        return $this->jsonParser->decode($this->fileReader->read($path));
    }
}
