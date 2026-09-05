<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PriceRetrievalResult;
use App\Dto\PrizeWallItem;

class PriceTrendRetriever
{
    public function __construct(
        private readonly PriceCacheInterface $priceCache,
        private readonly ProductMappingRepositoryInterface $productMappings,
        private readonly TrendPriceProviderInterface $trendPriceProvider,
    ) {
    }

    /**
     * @param PrizeWallItem[] $items
     */
    public function getPrices(string $organizer, array $items): PriceRetrievalResult
    {
        [$pricedFromCache, $needingLookup] = $this->priceFromCache(
            organizer: $organizer,
            items:     $items,
        );

        if ($needingLookup === []) {
            return new PriceRetrievalResult(
                prizeWallItems:         $pricedFromCache,
                prizeWallItemsNotFound: [],
            );
        }

        [$pricedFromCardmarket, $notFound] = $this->priceFromCardmarket(
            organizer: $organizer,
            items:     $needingLookup,
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
    private function priceFromCache(string $organizer, array $items): array
    {
        $cachedPrices = $this->priceCache->pricesFor($organizer, $this->namesOf($items));

        $priced        = [];
        $needingLookup = [];

        foreach ($items as $item) {
            if (!isset($cachedPrices[$item->name])) {
                $needingLookup[] = $item;
                continue;
            }

            $item->eurPrice = $cachedPrices[$item->name];
            $priced[] = $item;
        }

        return [$priced, $needingLookup];
    }

    /**
     * @param PrizeWallItem[] $items
     * @return array{0: PrizeWallItem[], 1: PrizeWallItem[]} priced items, then items without a usable price
     */
    private function priceFromCardmarket(string $organizer, array $items): array
    {
        $productIds = $this->productMappings->productIdsFor($organizer, $this->namesOf($items));

        $priced   = [];
        $notFound = [];

        foreach ($items as $item) {
            $trendPrice = isset($productIds[$item->name])
                ? $this->trendPriceProvider->trendPriceFor($productIds[$item->name])
                : null;

            if ($trendPrice === null) {
                $notFound[] = $item;
                continue;
            }

            $item->eurPrice = $trendPrice;
            $priced[] = $item;
        }

        return [$priced, $notFound];
    }

    /**
     * @param PrizeWallItem[] $items
     * @return string[]
     */
    private function namesOf(array $items): array
    {
        return array_map(static fn (PrizeWallItem $item): string => $item->name, $items);
    }
}
