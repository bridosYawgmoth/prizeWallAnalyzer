<?php

declare(strict_types=1);

namespace App\Service;

use App\Cardmarket\CardmarketClientInterface;
use App\Dto\PriceRetrievalResult;
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
        private readonly string $mappingDir,
    ) {
    }

    /**
     * @param PrizeWallItem[] $items
     */
    public function getPrices(string $organizer, array $items): PriceRetrievalResult
    {
        $cache      = $this->readCache($organizer);
        $found      = [];
        $notInCache = [];

        foreach ($items as $item) {
            if ($this->isInCache(name: $item->name, cache: $cache)) {
                $item->eurPrice = $this->readFromCache(name: $item->name, cache: $cache);
                $found[] = $item;
                continue;
            }

            $notInCache[] = $item;
        }

        if ($notInCache === []) {
            return new PriceRetrievalResult(
                prizeWallItems:         $found,
                prizeWallItemsNotFound: [],
            );
        }

        $mapping  = $this->readMapping($organizer);
        $notFound = [];

        foreach ($notInCache as $item) {
            if (!$this->isInMapping(name: $item->name, mapping: $mapping)) {
                $notFound[] = $item;
                continue;
            }

            $trendPrice = $this->readFromCardmarket(name: $item->name, mapping: $mapping);

            if ($trendPrice === null) {
                $notFound[] = $item;
                continue;
            }

            $item->eurPrice = $trendPrice;
            $found[] = $item;
        }

        return new PriceRetrievalResult(
            prizeWallItems:         $found,
            prizeWallItemsNotFound: $notFound,
        );
    }

    private function readFromCardmarket(string $name, array $mapping): ?float
    {
        $productId = $mapping[$name]['cardmarket_product_id'];
        $response  = $this->cardmarketClient->getProduct(productId: $productId);

        if (!isset($response['product']['priceGuide']['TREND'])) {
            return null;
        }

        return (float) $response['product']['priceGuide']['TREND'];
    }

    private function readMapping(string $organizer): array
    {
        $path = sprintf('%s/%s/product_mappings.json', $this->mappingDir, $organizer);

        return $this->jsonParser->decode($this->fileReader->read($path));
    }

    private function readFromCache(string $name, array $cache): float
    {
        return (float) $cache[$this->normalizeName($name)];
    }

    private function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }

    private function isInMapping(string $name, array $mapping): bool
    {
        return isset($mapping[$name]);
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
