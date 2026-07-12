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

        $this->assertCount(1, $result);
        $this->assertSame(18.50, $result[0]->eurPrice);
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

        $this->assertCount(1, $result);
        $this->assertSame(18.50, $result[0]->eurPrice);
    }
}
