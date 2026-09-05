<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\PrizeWallItem;
use App\Service\PriceCacheInterface;
use App\Service\PriceTrendRetriever;
use App\Service\ProductMappingRepositoryInterface;
use App\Service\TrendPriceProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PriceTrendRetrieverTest extends TestCase
{
    private const string KAMIGAWA = 'Kamigawa Neon Dynasty Collector Booster';
    private const string MODERN_HORIZONS = 'Modern Horizons 3 Collector Booster';

    private PriceCacheInterface&MockObject $priceCache;
    private ProductMappingRepositoryInterface&MockObject $productMappings;
    private TrendPriceProviderInterface&MockObject $trendPriceProvider;
    private PriceTrendRetriever $retriever;

    protected function setUp(): void
    {
        $this->priceCache         = $this->createMock(PriceCacheInterface::class);
        $this->productMappings    = $this->createMock(ProductMappingRepositoryInterface::class);
        $this->trendPriceProvider = $this->createMock(TrendPriceProviderInterface::class);

        $this->retriever = new PriceTrendRetriever(
            priceCache:         $this->priceCache,
            productMappings:    $this->productMappings,
            trendPriceProvider: $this->trendPriceProvider,
        );
    }

    public function testGetPricesReturnsCachedPriceWithoutConsultingCardmarket(): void
    {
        $item = new PrizeWallItem(name: self::KAMIGAWA, tixPrice: 10);

        $this->stubCachedPrices(names: [self::KAMIGAWA], prices: [self::KAMIGAWA => 18.50]);
        $this->productMappings->expects($this->never())->method('productIdsFor');
        $this->trendPriceProvider->expects($this->never())->method('trendPriceFor');

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(1, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
    }

    public function testGetPricesReturnsTwoCachedPricesWithoutConsultingCardmarket(): void
    {
        $item1 = new PrizeWallItem(name: self::KAMIGAWA, tixPrice: 10);
        $item2 = new PrizeWallItem(name: self::MODERN_HORIZONS, tixPrice: 12);

        $this->stubCachedPrices(
            names: [self::KAMIGAWA, self::MODERN_HORIZONS],
            prices: [self::KAMIGAWA => 18.50, self::MODERN_HORIZONS => 22.00],
        );
        $this->productMappings->expects($this->never())->method('productIdsFor');
        $this->trendPriceProvider->expects($this->never())->method('trendPriceFor');

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item1, $item2]);

        $this->assertCount(2, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
        $this->assertSame(22.00, $result->prizeWallItems[1]->eurPrice);
    }

    public function testGetPricesFetchesFromCardmarketWhenItemIsNotInCache(): void
    {
        $item = new PrizeWallItem(name: self::KAMIGAWA, tixPrice: 10);

        $this->stubCachedPrices(names: [self::KAMIGAWA], prices: []);
        $this->stubProductIds(names: [self::KAMIGAWA], productIds: [self::KAMIGAWA => 587688]);
        $this->trendPriceProvider
            ->expects($this->once())
            ->method('trendPriceFor')
            ->with(587688)
            ->willReturn(18.50);

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(1, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
    }

    public function testGetPricesFetchesTwoItemsFromCardmarketWhenNeitherIsInCache(): void
    {
        $item1 = new PrizeWallItem(name: self::KAMIGAWA, tixPrice: 10);
        $item2 = new PrizeWallItem(name: self::MODERN_HORIZONS, tixPrice: 12);

        $this->stubCachedPrices(names: [self::KAMIGAWA, self::MODERN_HORIZONS], prices: []);
        $this->stubProductIds(
            names: [self::KAMIGAWA, self::MODERN_HORIZONS],
            productIds: [self::KAMIGAWA => 587688, self::MODERN_HORIZONS => 758481],
        );
        $this->stubTrendPrices([587688 => 18.50, 758481 => 22.00]);

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item1, $item2]);

        $this->assertCount(2, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
        $this->assertSame(22.00, $result->prizeWallItems[1]->eurPrice);
    }

    public function testGetPricesReturnsOneItemFromCacheAndOneFromCardmarket(): void
    {
        $cachedItem = new PrizeWallItem(name: self::KAMIGAWA, tixPrice: 10);
        $cmItem     = new PrizeWallItem(name: self::MODERN_HORIZONS, tixPrice: 12);

        $this->stubCachedPrices(
            names: [self::KAMIGAWA, self::MODERN_HORIZONS],
            prices: [self::KAMIGAWA => 18.50],
        );
        $this->stubProductIds(
            names: [self::MODERN_HORIZONS],
            productIds: [self::MODERN_HORIZONS => 758481],
        );
        $this->trendPriceProvider
            ->expects($this->once())
            ->method('trendPriceFor')
            ->with(758481)
            ->willReturn(22.00);

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$cachedItem, $cmItem]);

        $this->assertCount(2, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
        $this->assertSame(22.00, $result->prizeWallItems[1]->eurPrice);
    }

    public function testGetPricesMovesItemToNotFoundWhenMappingEntryIsMissing(): void
    {
        $item = new PrizeWallItem(name: 'Unknown Product', tixPrice: 10);

        $this->stubCachedPrices(names: ['Unknown Product'], prices: []);
        $this->stubProductIds(names: ['Unknown Product'], productIds: []);
        $this->trendPriceProvider->expects($this->never())->method('trendPriceFor');

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(0, $result->prizeWallItems);
        $this->assertCount(1, $result->prizeWallItemsNotFound);
        $this->assertSame($item, $result->prizeWallItemsNotFound[0]);
        $this->assertNull($result->prizeWallItemsNotFound[0]->eurPrice);
    }

    public function testGetPricesMovesItemToNotFoundWhenNoTrendPriceIsAvailable(): void
    {
        $item = new PrizeWallItem(name: self::KAMIGAWA, tixPrice: 10);

        $this->stubCachedPrices(names: [self::KAMIGAWA], prices: []);
        $this->stubProductIds(names: [self::KAMIGAWA], productIds: [self::KAMIGAWA => 587688]);
        $this->trendPriceProvider
            ->expects($this->once())
            ->method('trendPriceFor')
            ->with(587688)
            ->willReturn(null);

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(0, $result->prizeWallItems);
        $this->assertCount(1, $result->prizeWallItemsNotFound);
        $this->assertSame($item, $result->prizeWallItemsNotFound[0]);
        $this->assertNull($result->prizeWallItemsNotFound[0]->eurPrice);
    }

    public function testGetPricesSplitsItemsAcrossFoundAndNotFoundInOneCall(): void
    {
        $cachedItem   = new PrizeWallItem(name: self::KAMIGAWA, tixPrice: 10);
        $cmItem       = new PrizeWallItem(name: self::MODERN_HORIZONS, tixPrice: 12);
        $unmappedItem = new PrizeWallItem(name: 'Unknown Product', tixPrice: 8);

        $this->stubCachedPrices(
            names: [self::KAMIGAWA, self::MODERN_HORIZONS, 'Unknown Product'],
            prices: [self::KAMIGAWA => 18.50],
        );
        $this->stubProductIds(
            names: [self::MODERN_HORIZONS, 'Unknown Product'],
            productIds: [self::MODERN_HORIZONS => 758481],
        );
        $this->trendPriceProvider
            ->expects($this->once())
            ->method('trendPriceFor')
            ->with(758481)
            ->willReturn(22.00);

        $result = $this->retriever->getPrices(
            organizer: 'pastimeevents',
            items: [$cachedItem, $cmItem, $unmappedItem],
        );

        $this->assertCount(2, $result->prizeWallItems);
        $this->assertCount(1, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
        $this->assertSame(22.00, $result->prizeWallItems[1]->eurPrice);
        $this->assertSame($unmappedItem, $result->prizeWallItemsNotFound[0]);
        $this->assertNull($result->prizeWallItemsNotFound[0]->eurPrice);
    }

    /**
     * @param string[] $names
     * @param array<string, float> $prices
     */
    private function stubCachedPrices(array $names, array $prices): void
    {
        $this->priceCache
            ->expects($this->once())
            ->method('pricesFor')
            ->with('pastimeevents', $names)
            ->willReturn($prices);
    }

    /**
     * @param string[] $names
     * @param array<string, int> $productIds
     */
    private function stubProductIds(array $names, array $productIds): void
    {
        $this->productMappings
            ->expects($this->once())
            ->method('productIdsFor')
            ->with('pastimeevents', $names)
            ->willReturn($productIds);
    }

    /**
     * @param array<int, float> $trendPricesByProductId
     */
    private function stubTrendPrices(array $trendPricesByProductId): void
    {
        $this->trendPriceProvider
            ->expects($this->exactly(count($trendPricesByProductId)))
            ->method('trendPriceFor')
            ->willReturnCallback(
                static fn (int $productId): ?float => $trendPricesByProductId[$productId] ?? null,
            );
    }
}
