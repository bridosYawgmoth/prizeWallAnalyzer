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
        );
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
            ->method('get');

        $result = $this->retriever->getPrices(organizer: 'pastimeevents', items: [$item]);

        $this->assertCount(1, $result);
        $this->assertSame(18.50, $result[0]->eurPrice);
    }
}
