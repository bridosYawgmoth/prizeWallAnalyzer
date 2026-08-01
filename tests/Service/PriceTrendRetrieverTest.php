<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Cardmarket\CardmarketClientInterface;
use App\Dto\PrizeWallItem;
use App\Infrastructure\FileReaderRepositoryInterface;
use App\Infrastructure\JsonParserInterface;
use App\Service\PriceTrendRetriever;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PriceTrendRetrieverTest extends TestCase
{
    private CardmarketClientInterface&MockObject $cardmarketClient;
    private FileReaderRepositoryInterface&MockObject $fileReader;
    private JsonParserInterface&MockObject $jsonParser;
    private PriceTrendRetriever $retriever;

    protected function setUp(): void
    {
        $this->cardmarketClient = $this->createMock(CardmarketClientInterface::class);
        $this->fileReader       = $this->createMock(FileReaderRepositoryInterface::class);
        $this->jsonParser       = $this->createMock(JsonParserInterface::class);

        $this->retriever = new PriceTrendRetriever(
            cardmarketClient: $this->cardmarketClient,
            fileReader:       $this->fileReader,
            jsonParser:       $this->jsonParser,
            cacheDir:         '/tmp/prices',
            mappingDir:       '/tmp/mappings',
        );
    }

    public function testGetPricesFetchesFromCardmarketWhenItemIsNotInCache(): void
    {
        $item = new PrizeWallItem(name: 'Kamigawa Neon Dynasty Collector Booster', tixPrice: 10);

        $emptyCache  = '{}';
        $mappingJson = '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688,"cardmarket_name":"Kamigawa: Neon Dynasty Collector Booster"}}';
        $mappingData = [
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_product_id' => 587688,
                'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
        ];
        $cmResponse = [
            'product' => [
                'idProduct'  => 587688,
                'priceGuide' => ['TREND' => 18.50],
            ],
        ];

        $readPaths   = ['/tmp/prices/pastimeevents.json', '/tmp/mappings/pastimeevents/product_mappings.json'];
        $readReturns = [$emptyCache, $mappingJson];

        $this->fileReader
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function (string $path) use ($readPaths, $readReturns): string {
                static $callIndex = 0;
                $this->assertSame($readPaths[$callIndex], $path);

                return $readReturns[$callIndex++];
            });

        $decodeInputs  = [$emptyCache, $mappingJson];
        $decodeReturns = [[], $mappingData];

        $this->jsonParser
            ->expects($this->exactly(2))
            ->method('decode')
            ->willReturnCallback(function (string $json) use ($decodeInputs, $decodeReturns): array {
                static $callIndex = 0;
                $this->assertSame($decodeInputs[$callIndex], $json);

                return $decodeReturns[$callIndex++];
            });

        $this->cardmarketClient
            ->expects($this->once())
            ->method('getProduct')
            ->with(587688)
            ->willReturn($cmResponse);

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(1, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
    }

    public function testGetPricesMovesItemToNotFoundWhenCardmarketTrendPriceIsMissing(): void
    {
        $item = new PrizeWallItem(name: 'Kamigawa Neon Dynasty Collector Booster', tixPrice: 10);

        $emptyCache  = '{}';
        $mappingJson = '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688,"cardmarket_name":"Kamigawa: Neon Dynasty Collector Booster"}}';
        $mappingData = [
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_product_id' => 587688,
                'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
        ];
        $cmResponse = [
            'product' => [
                'idProduct'  => 587688,
                'priceGuide' => [],
            ],
        ];

        $readPaths   = ['/tmp/prices/pastimeevents.json', '/tmp/mappings/pastimeevents/product_mappings.json'];
        $readReturns = [$emptyCache, $mappingJson];

        $this->fileReader
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function (string $path) use ($readPaths, $readReturns): string {
                static $callIndex = 0;
                $this->assertSame($readPaths[$callIndex], $path);

                return $readReturns[$callIndex++];
            });

        $decodeInputs  = [$emptyCache, $mappingJson];
        $decodeReturns = [[], $mappingData];

        $this->jsonParser
            ->expects($this->exactly(2))
            ->method('decode')
            ->willReturnCallback(function (string $json) use ($decodeInputs, $decodeReturns): array {
                static $callIndex = 0;
                $this->assertSame($decodeInputs[$callIndex], $json);

                return $decodeReturns[$callIndex++];
            });

        $this->cardmarketClient
            ->expects($this->once())
            ->method('getProduct')
            ->with(587688)
            ->willReturn($cmResponse);

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(0, $result->prizeWallItems);
        $this->assertCount(1, $result->prizeWallItemsNotFound);
        $this->assertSame($item, $result->prizeWallItemsNotFound[0]);
        $this->assertNull($result->prizeWallItemsNotFound[0]->eurPrice);
    }

    public function testGetPricesMovesItemToNotFoundWhenCardmarketReturnsNoProduct(): void
    {
        $item = new PrizeWallItem(name: 'Kamigawa Neon Dynasty Collector Booster', tixPrice: 10);

        $emptyCache  = '{}';
        $mappingJson = '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688,"cardmarket_name":"Kamigawa: Neon Dynasty Collector Booster"}}';
        $mappingData = [
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_product_id' => 587688,
                'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
        ];

        $readPaths   = ['/tmp/prices/pastimeevents.json', '/tmp/mappings/pastimeevents/product_mappings.json'];
        $readReturns = [$emptyCache, $mappingJson];

        $this->fileReader
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function (string $path) use ($readPaths, $readReturns): string {
                static $callIndex = 0;
                $this->assertSame($readPaths[$callIndex], $path);

                return $readReturns[$callIndex++];
            });

        $decodeInputs  = [$emptyCache, $mappingJson];
        $decodeReturns = [[], $mappingData];

        $this->jsonParser
            ->expects($this->exactly(2))
            ->method('decode')
            ->willReturnCallback(function (string $json) use ($decodeInputs, $decodeReturns): array {
                static $callIndex = 0;
                $this->assertSame($decodeInputs[$callIndex], $json);

                return $decodeReturns[$callIndex++];
            });

        $this->cardmarketClient
            ->expects($this->once())
            ->method('getProduct')
            ->with(587688)
            ->willReturn([]);

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(0, $result->prizeWallItems);
        $this->assertCount(1, $result->prizeWallItemsNotFound);
        $this->assertSame($item, $result->prizeWallItemsNotFound[0]);
        $this->assertNull($result->prizeWallItemsNotFound[0]->eurPrice);
    }

    public function testGetPricesMovesItemToNotFoundWhenCardmarketRequestFails(): void
    {
        $item = new PrizeWallItem(name: 'Kamigawa Neon Dynasty Collector Booster', tixPrice: 10);

        $emptyCache  = '{}';
        $mappingJson = '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688,"cardmarket_name":"Kamigawa: Neon Dynasty Collector Booster"}}';
        $mappingData = [
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_product_id' => 587688,
                'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
        ];

        $readPaths   = ['/tmp/prices/pastimeevents.json', '/tmp/mappings/pastimeevents/product_mappings.json'];
        $readReturns = [$emptyCache, $mappingJson];

        $this->fileReader
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function (string $path) use ($readPaths, $readReturns): string {
                static $callIndex = 0;
                $this->assertSame($readPaths[$callIndex], $path);

                return $readReturns[$callIndex++];
            });

        $decodeInputs  = [$emptyCache, $mappingJson];
        $decodeReturns = [[], $mappingData];

        $this->jsonParser
            ->expects($this->exactly(2))
            ->method('decode')
            ->willReturnCallback(function (string $json) use ($decodeInputs, $decodeReturns): array {
                static $callIndex = 0;
                $this->assertSame($decodeInputs[$callIndex], $json);

                return $decodeReturns[$callIndex++];
            });

        $this->cardmarketClient
            ->expects($this->once())
            ->method('getProduct')
            ->with(587688)
            ->willThrowException(new RuntimeException('Cardmarket request failed'));

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(0, $result->prizeWallItems);
        $this->assertCount(1, $result->prizeWallItemsNotFound);
        $this->assertSame($item, $result->prizeWallItemsNotFound[0]);
        $this->assertNull($result->prizeWallItemsNotFound[0]->eurPrice);
    }

    public function testGetPricesMovesItemToNotFoundWhenMappingEntryIsMissing(): void
    {
        $item = new PrizeWallItem(name: 'Unknown Product', tixPrice: 10);

        $emptyCache  = '{}';
        $mappingJson = '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688,"cardmarket_name":"Kamigawa: Neon Dynasty Collector Booster"}}';
        $mappingData = [
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_product_id' => 587688,
                'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
        ];

        $readPaths   = ['/tmp/prices/pastimeevents.json', '/tmp/mappings/pastimeevents/product_mappings.json'];
        $readReturns = [$emptyCache, $mappingJson];

        $this->fileReader
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function (string $path) use ($readPaths, $readReturns): string {
                static $callIndex = 0;
                $this->assertSame($readPaths[$callIndex], $path);

                return $readReturns[$callIndex++];
            });

        $decodeInputs  = [$emptyCache, $mappingJson];
        $decodeReturns = [[], $mappingData];

        $this->jsonParser
            ->expects($this->exactly(2))
            ->method('decode')
            ->willReturnCallback(function (string $json) use ($decodeInputs, $decodeReturns): array {
                static $callIndex = 0;
                $this->assertSame($decodeInputs[$callIndex], $json);

                return $decodeReturns[$callIndex++];
            });

        $this->cardmarketClient
            ->expects($this->never())
            ->method('getProduct');

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(0, $result->prizeWallItems);
        $this->assertCount(1, $result->prizeWallItemsNotFound);
        $this->assertSame($item, $result->prizeWallItemsNotFound[0]);
        $this->assertNull($result->prizeWallItemsNotFound[0]->eurPrice);
    }

    public function testGetPricesReturnsTwoItemsFromCacheWithoutCallingCardmarketApi(): void
    {
        $item1 = new PrizeWallItem(name: 'Kamigawa Neon Dynasty Collector Booster', tixPrice: 10);
        $item2 = new PrizeWallItem(name: 'Modern Horizons 3 Collector Booster', tixPrice: 12);

        $cachedJson = '{"kamigawa neon dynasty collector booster":18.50,"modern horizons 3 collector booster":22.00}';
        $cachedData = [
            'kamigawa neon dynasty collector booster' => 18.50,
            'modern horizons 3 collector booster'       => 22.00,
        ];

        $this->fileReader
            ->expects($this->once())
            ->method('read')
            ->with('/tmp/prices/pastimeevents.json')
            ->willReturn($cachedJson);

        $this->jsonParser
            ->expects($this->once())
            ->method('decode')
            ->with($cachedJson)
            ->willReturn($cachedData);

        $this->cardmarketClient
            ->expects($this->never())
            ->method('getProduct');

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item1, $item2]);

        $this->assertCount(2, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
        $this->assertSame(22.00, $result->prizeWallItems[1]->eurPrice);
    }

    public function testGetPricesFetchesTwoItemsFromCardmarketWhenNeitherIsInCache(): void
    {
        $item1 = new PrizeWallItem(name: 'Kamigawa Neon Dynasty Collector Booster', tixPrice: 10);
        $item2 = new PrizeWallItem(name: 'Modern Horizons 3 Collector Booster', tixPrice: 12);

        $emptyCache  = '{}';
        $mappingJson = '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688,"cardmarket_name":"Kamigawa: Neon Dynasty Collector Booster"},"Modern Horizons 3 Collector Booster":{"cardmarket_product_id":758481,"cardmarket_name":"Modern Horizons 3 Collector Booster"}}';
        $mappingData = [
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_product_id' => 587688,
                'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
            'Modern Horizons 3 Collector Booster' => [
                'cardmarket_product_id' => 758481,
                'cardmarket_name'       => 'Modern Horizons 3 Collector Booster',
            ],
        ];
        $kamigawaResponse = [
            'product' => [
                'idProduct'  => 587688,
                'priceGuide' => ['TREND' => 18.50],
            ],
        ];
        $modernHorizonsResponse = [
            'product' => [
                'idProduct'  => 758481,
                'priceGuide' => ['TREND' => 22.00],
            ],
        ];

        $readPaths   = ['/tmp/prices/pastimeevents.json', '/tmp/mappings/pastimeevents/product_mappings.json'];
        $readReturns = [$emptyCache, $mappingJson];

        $this->fileReader
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function (string $path) use ($readPaths, $readReturns): string {
                static $callIndex = 0;
                $this->assertSame($readPaths[$callIndex], $path);

                return $readReturns[$callIndex++];
            });

        $decodeInputs  = [$emptyCache, $mappingJson];
        $decodeReturns = [[], $mappingData];

        $this->jsonParser
            ->expects($this->exactly(2))
            ->method('decode')
            ->willReturnCallback(function (string $json) use ($decodeInputs, $decodeReturns): array {
                static $callIndex = 0;
                $this->assertSame($decodeInputs[$callIndex], $json);

                return $decodeReturns[$callIndex++];
            });

        $productIds = [587688, 758481];
        $responses  = [$kamigawaResponse, $modernHorizonsResponse];

        $this->cardmarketClient
            ->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturnCallback(function (int $productId) use ($productIds, $responses): array {
                static $callIndex = 0;
                $this->assertSame($productIds[$callIndex], $productId);

                return $responses[$callIndex++];
            });

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item1, $item2]);

        $this->assertCount(2, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
        $this->assertSame(22.00, $result->prizeWallItems[1]->eurPrice);
    }

    public function testGetPricesReturnsOneItemFromCacheAndOneFromCardmarket(): void
    {
        $cachedItem = new PrizeWallItem(name: 'Kamigawa Neon Dynasty Collector Booster', tixPrice: 10);
        $cmItem     = new PrizeWallItem(name: 'Modern Horizons 3 Collector Booster', tixPrice: 12);

        $cachedJson = '{"kamigawa neon dynasty collector booster":18.50}';
        $cachedData = ['kamigawa neon dynasty collector booster' => 18.50];
        $mappingJson = '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688,"cardmarket_name":"Kamigawa: Neon Dynasty Collector Booster"},"Modern Horizons 3 Collector Booster":{"cardmarket_product_id":758481,"cardmarket_name":"Modern Horizons 3 Collector Booster"}}';
        $mappingData = [
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_product_id' => 587688,
                'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
            'Modern Horizons 3 Collector Booster' => [
                'cardmarket_product_id' => 758481,
                'cardmarket_name'       => 'Modern Horizons 3 Collector Booster',
            ],
        ];
        $cmResponse = [
            'product' => [
                'idProduct'  => 758481,
                'priceGuide' => ['TREND' => 22.00],
            ],
        ];

        $readPaths   = ['/tmp/prices/pastimeevents.json', '/tmp/mappings/pastimeevents/product_mappings.json'];
        $readReturns = [$cachedJson, $mappingJson];

        $this->fileReader
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function (string $path) use ($readPaths, $readReturns): string {
                static $callIndex = 0;
                $this->assertSame($readPaths[$callIndex], $path);

                return $readReturns[$callIndex++];
            });

        $decodeInputs  = [$cachedJson, $mappingJson];
        $decodeReturns = [$cachedData, $mappingData];

        $this->jsonParser
            ->expects($this->exactly(2))
            ->method('decode')
            ->willReturnCallback(function (string $json) use ($decodeInputs, $decodeReturns): array {
                static $callIndex = 0;
                $this->assertSame($decodeInputs[$callIndex], $json);

                return $decodeReturns[$callIndex++];
            });

        $this->cardmarketClient
            ->expects($this->once())
            ->method('getProduct')
            ->with(758481)
            ->willReturn($cmResponse);

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$cachedItem, $cmItem]);

        $this->assertCount(2, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
        $this->assertSame(22.00, $result->prizeWallItems[1]->eurPrice);
    }

    public function testGetPricesSplitsItemsAcrossFoundAndNotFoundInOneCall(): void
    {
        $cachedItem   = new PrizeWallItem(name: 'Kamigawa Neon Dynasty Collector Booster', tixPrice: 10);
        $cmItem       = new PrizeWallItem(name: 'Modern Horizons 3 Collector Booster', tixPrice: 12);
        $unmappedItem = new PrizeWallItem(name: 'Unknown Product', tixPrice: 8);

        $cachedJson = '{"kamigawa neon dynasty collector booster":18.50}';
        $cachedData = ['kamigawa neon dynasty collector booster' => 18.50];
        $mappingJson = '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688,"cardmarket_name":"Kamigawa: Neon Dynasty Collector Booster"},"Modern Horizons 3 Collector Booster":{"cardmarket_product_id":758481,"cardmarket_name":"Modern Horizons 3 Collector Booster"}}';
        $mappingData = [
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_product_id' => 587688,
                'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
            'Modern Horizons 3 Collector Booster' => [
                'cardmarket_product_id' => 758481,
                'cardmarket_name'       => 'Modern Horizons 3 Collector Booster',
            ],
        ];
        $cmResponse = [
            'product' => [
                'idProduct'  => 758481,
                'priceGuide' => ['TREND' => 22.00],
            ],
        ];

        $readPaths   = ['/tmp/prices/pastimeevents.json', '/tmp/mappings/pastimeevents/product_mappings.json'];
        $readReturns = [$cachedJson, $mappingJson];

        $this->fileReader
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function (string $path) use ($readPaths, $readReturns): string {
                static $callIndex = 0;
                $this->assertSame($readPaths[$callIndex], $path);

                return $readReturns[$callIndex++];
            });

        $decodeInputs  = [$cachedJson, $mappingJson];
        $decodeReturns = [$cachedData, $mappingData];

        $this->jsonParser
            ->expects($this->exactly(2))
            ->method('decode')
            ->willReturnCallback(function (string $json) use ($decodeInputs, $decodeReturns): array {
                static $callIndex = 0;
                $this->assertSame($decodeInputs[$callIndex], $json);

                return $decodeReturns[$callIndex++];
            });

        $this->cardmarketClient
            ->expects($this->once())
            ->method('getProduct')
            ->with(758481)
            ->willReturn($cmResponse);

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

    public function testGetPricesReturnsCachedTrendPriceWithoutCallingCardmarketApi(): void
    {
        $item = new PrizeWallItem(name: 'Kamigawa Neon Dynasty Collector Booster', tixPrice: 10);

        $cachedJson = '{"kamigawa neon dynasty collector booster":18.50}';
        $cachedData = ['kamigawa neon dynasty collector booster' => 18.50];

        $this->fileReader
            ->expects($this->once())
            ->method('read')
            ->with('/tmp/prices/pastimeevents.json')
            ->willReturn($cachedJson);

        $this->jsonParser
            ->expects($this->once())
            ->method('decode')
            ->with($cachedJson)
            ->willReturn($cachedData);

        $this->cardmarketClient
            ->expects($this->never())
            ->method('getProduct');

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(1, $result->prizeWallItems);
        $this->assertCount(0, $result->prizeWallItemsNotFound);
        $this->assertSame(18.50, $result->prizeWallItems[0]->eurPrice);
    }
}
