<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Infrastructure\FileReaderRepositoryInterface;
use App\Infrastructure\JsonParserInterface;
use App\Service\PriceCache;
use PHPUnit\Framework\TestCase;

final class PriceCacheTest extends TestCase
{
    private const string CACHE_DIR = '/tmp/prices';

    public function testPricesForReadsTheCacheFileOfTheOrganizer(): void
    {
        $fileReader = $this->createMock(FileReaderRepositoryInterface::class);
        $fileReader->method('exists')->willReturn(true);
        $fileReader
            ->expects($this->once())
            ->method('read')
            ->with(self::CACHE_DIR . '/pastimeevents.json')
            ->willReturn('{}');

        $priceCache = new PriceCache(
            fileReader: $fileReader,
            jsonParser: $this->jsonParserReturning([]),
            cacheDir:   self::CACHE_DIR,
        );

        $priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );
    }

    public function testPricesForReadsTheCacheOnceRegardlessOfTheNumberOfNames(): void
    {
        $json = '{"kamigawa neon dynasty collector booster":18.50}';

        $fileReader = $this->createMock(FileReaderRepositoryInterface::class);
        $fileReader->method('exists')->willReturn(true);
        $fileReader
            ->expects($this->once())
            ->method('read')
            ->willReturn($json);

        $jsonParser = $this->createMock(JsonParserInterface::class);
        $jsonParser
            ->expects($this->once())
            ->method('decode')
            ->with($json)
            ->willReturn(['kamigawa neon dynasty collector booster' => 18.50]);

        $priceCache = new PriceCache(
            fileReader: $fileReader,
            jsonParser: $jsonParser,
            cacheDir:   self::CACHE_DIR,
        );

        $priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: [
                'Kamigawa Neon Dynasty Collector Booster',
                'Modern Horizons 3 Collector Booster',
                'Bloomburrow Play Booster',
                'Foundations Jumpstart Booster',
            ],
        );
    }

    public function testPricesForReturnsNoPricesWhenTheCacheFileDoesNotExist(): void
    {
        $fileReader = $this->createMock(FileReaderRepositoryInterface::class);
        $fileReader
            ->expects($this->once())
            ->method('exists')
            ->with(self::CACHE_DIR . '/pastimeevents.json')
            ->willReturn(false);
        $fileReader
            ->expects($this->never())
            ->method('read');

        $priceCache = new PriceCache(
            fileReader: $fileReader,
            jsonParser: $this->jsonParserReturning([]),
            cacheDir:   self::CACHE_DIR,
        );

        $prices = $priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame([], $prices);
    }

    public function testPricesForReturnsCachedPriceKeyedByRequestedName(): void
    {
        $priceCache = $this->priceCacheContaining(['kamigawa neon dynasty collector booster' => 18.50]);

        $prices = $priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 18.50], $prices);
    }

    public function testPricesForMatchesCachedNamesRegardlessOfCase(): void
    {
        $priceCache = $this->priceCacheContaining(['kamigawa neon dynasty collector booster' => 18.50]);

        $prices = $priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['KAMIGAWA NEON DYNASTY COLLECTOR BOOSTER'],
        );

        $this->assertSame(['KAMIGAWA NEON DYNASTY COLLECTOR BOOSTER' => 18.50], $prices);
    }

    public function testPricesForIgnoresSurroundingWhitespaceInRequestedNames(): void
    {
        $priceCache = $this->priceCacheContaining(['kamigawa neon dynasty collector booster' => 18.50]);

        $prices = $priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['  Kamigawa Neon Dynasty Collector Booster  '],
        );

        $this->assertSame(['  Kamigawa Neon Dynasty Collector Booster  ' => 18.50], $prices);
    }

    public function testPricesForOmitsNamesWithoutACachedPrice(): void
    {
        $priceCache = $this->priceCacheContaining(['kamigawa neon dynasty collector booster' => 18.50]);

        $prices = $priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster', 'Modern Horizons 3 Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 18.50], $prices);
    }

    public function testPricesForReturnsNoPricesWhenTheCacheIsEmpty(): void
    {
        $priceCache = $this->priceCacheContaining([]);

        $prices = $priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame([], $prices);
    }

    public function testPricesForReturnsCachedPricesAsFloats(): void
    {
        $priceCache = $this->priceCacheContaining(['kamigawa neon dynasty collector booster' => 18]);

        $prices = $priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 18.0], $prices);
    }

    /**
     * @param array<string, float|int> $cache decoded cache contents, keyed by normalized name
     */
    private function priceCacheContaining(array $cache): PriceCache
    {
        $fileReader = $this->createStub(FileReaderRepositoryInterface::class);
        $fileReader->method('exists')->willReturn(true);
        $fileReader->method('read')->willReturn('{}');

        return new PriceCache(
            fileReader: $fileReader,
            jsonParser: $this->jsonParserReturning($cache),
            cacheDir:   self::CACHE_DIR,
        );
    }

    /**
     * @param array<string, float|int> $data
     */
    private function jsonParserReturning(array $data): JsonParserInterface
    {
        $jsonParser = $this->createStub(JsonParserInterface::class);
        $jsonParser->method('decode')->willReturn($data);

        return $jsonParser;
    }
}
