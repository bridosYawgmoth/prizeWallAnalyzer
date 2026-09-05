<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Infrastructure\FileReaderRepositoryInterface;
use App\Infrastructure\JsonParserInterface;
use App\Service\PriceCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PriceCacheTest extends TestCase
{
    private FileReaderRepositoryInterface&MockObject $fileReader;
    private JsonParserInterface&MockObject $jsonParser;
    private PriceCache $priceCache;

    protected function setUp(): void
    {
        $this->fileReader = $this->createMock(FileReaderRepositoryInterface::class);
        $this->jsonParser = $this->createMock(JsonParserInterface::class);

        $this->priceCache = new PriceCache(
            fileReader: $this->fileReader,
            jsonParser: $this->jsonParser,
            cacheDir:   '/tmp/prices',
        );
    }

    public function testPricesForReadsTheCacheFileOfTheOrganizer(): void
    {
        $this->stubCache(
            path: '/tmp/prices/pastimeevents.json',
            json: '{}',
            data: [],
        );

        $this->priceCache->pricesFor(organizer: 'pastimeevents', names: ['Kamigawa Neon Dynasty Collector Booster']);
    }

    public function testPricesForReturnsCachedPriceKeyedByRequestedName(): void
    {
        $this->stubCache(
            path: '/tmp/prices/pastimeevents.json',
            json: '{"kamigawa neon dynasty collector booster":18.50}',
            data: ['kamigawa neon dynasty collector booster' => 18.50],
        );

        $prices = $this->priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 18.50], $prices);
    }

    public function testPricesForMatchesCachedNamesRegardlessOfCase(): void
    {
        $this->stubCache(
            path: '/tmp/prices/pastimeevents.json',
            json: '{"kamigawa neon dynasty collector booster":18.50}',
            data: ['kamigawa neon dynasty collector booster' => 18.50],
        );

        $prices = $this->priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['KAMIGAWA NEON DYNASTY COLLECTOR BOOSTER'],
        );

        $this->assertSame(['KAMIGAWA NEON DYNASTY COLLECTOR BOOSTER' => 18.50], $prices);
    }

    public function testPricesForIgnoresSurroundingWhitespaceInRequestedNames(): void
    {
        $this->stubCache(
            path: '/tmp/prices/pastimeevents.json',
            json: '{"kamigawa neon dynasty collector booster":18.50}',
            data: ['kamigawa neon dynasty collector booster' => 18.50],
        );

        $prices = $this->priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['  Kamigawa Neon Dynasty Collector Booster  '],
        );

        $this->assertSame(['  Kamigawa Neon Dynasty Collector Booster  ' => 18.50], $prices);
    }

    public function testPricesForOmitsNamesWithoutACachedPrice(): void
    {
        $this->stubCache(
            path: '/tmp/prices/pastimeevents.json',
            json: '{"kamigawa neon dynasty collector booster":18.50}',
            data: ['kamigawa neon dynasty collector booster' => 18.50],
        );

        $prices = $this->priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster', 'Modern Horizons 3 Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 18.50], $prices);
    }

    public function testPricesForReturnsNoPricesWhenTheCacheIsEmpty(): void
    {
        $this->stubCache(
            path: '/tmp/prices/pastimeevents.json',
            json: '{}',
            data: [],
        );

        $prices = $this->priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame([], $prices);
    }

    public function testPricesForReturnsCachedPricesAsFloats(): void
    {
        $this->stubCache(
            path: '/tmp/prices/pastimeevents.json',
            json: '{"kamigawa neon dynasty collector booster":18}',
            data: ['kamigawa neon dynasty collector booster' => 18],
        );

        $prices = $this->priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 18.0], $prices);
    }

    public function testPricesForReadsTheCacheOnceRegardlessOfTheNumberOfNames(): void
    {
        $this->stubCache(
            path: '/tmp/prices/pastimeevents.json',
            json: '{"kamigawa neon dynasty collector booster":18.50,"modern horizons 3 collector booster":22.00}',
            data: [
                'kamigawa neon dynasty collector booster' => 18.50,
                'modern horizons 3 collector booster'     => 22.00,
            ],
        );

        $prices = $this->priceCache->pricesFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster', 'Modern Horizons 3 Collector Booster'],
        );

        $this->assertSame(
            [
                'Kamigawa Neon Dynasty Collector Booster' => 18.50,
                'Modern Horizons 3 Collector Booster'     => 22.00,
            ],
            $prices,
        );
    }

    private function stubCache(string $path, string $json, array $data): void
    {
        $this->fileReader
            ->expects($this->once())
            ->method('read')
            ->with($path)
            ->willReturn($json);

        $this->jsonParser
            ->expects($this->once())
            ->method('decode')
            ->with($json)
            ->willReturn($data);
    }
}
