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
        [$pricedFromCache, $needingLookup] = $this->priceFromCache(
            items: $items,
            cache: $this->readCache($organizer),
        );

        if ($needingLookup === []) {
            return new PriceRetrievalResult(
                prizeWallItems:         $pricedFromCache,
                prizeWallItemsNotFound: [],
            );
        }

        [$pricedFromCardmarket, $notFound] = $this->priceFromCardmarket(
            items:   $needingLookup,
            mapping: $this->readMapping($organizer),
        );

        return new PriceRetrievalResult(
            prizeWallItems:         [...$pricedFromCache, ...$pricedFromCardmarket],
            prizeWallItemsNotFound: $notFound,
        );
    }

    /**
     * @param PrizeWallItem[] $items
     * @return array{0: PrizeWallItem[], 1: PrizeWallItem[]} priced items, then items needing a Cardmarket lookup
     */
    private function priceFromCache(array $items, array $cache): array
    {
        $priced        = [];
        $needingLookup = [];

        foreach ($items as $item) {
            if (!$this->isInCache(name: $item->name, cache: $cache)) {
                $needingLookup[] = $item;
                continue;
            }

            $item->eurPrice = $this->readFromCache(name: $item->name, cache: $cache);
            $priced[] = $item;
        }

        return [$priced, $needingLookup];
    }

    /**
     * @param PrizeWallItem[] $items
     * @return array{0: PrizeWallItem[], 1: PrizeWallItem[]} priced items, then items without a usable price
     */
    private function priceFromCardmarket(array $items, array $mapping): array
    {
        $priced   = [];
        $notFound = [];

        foreach ($items as $item) {
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
            $priced[] = $item;
        }

        return [$priced, $notFound];
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
