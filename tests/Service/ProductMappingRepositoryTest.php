<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Infrastructure\FileReaderRepositoryInterface;
use App\Infrastructure\JsonParserInterface;
use App\Service\ProductMappingRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProductMappingRepositoryTest extends TestCase
{
    private FileReaderRepositoryInterface&MockObject $fileReader;
    private JsonParserInterface&MockObject $jsonParser;
    private ProductMappingRepository $productMappings;

    protected function setUp(): void
    {
        $this->fileReader = $this->createMock(FileReaderRepositoryInterface::class);
        $this->jsonParser = $this->createMock(JsonParserInterface::class);

        $this->productMappings = new ProductMappingRepository(
            fileReader: $this->fileReader,
            jsonParser: $this->jsonParser,
            mappingDir: '/tmp/mappings',
        );
    }

    public function testProductIdsForReadsTheMappingFileOfTheOrganizer(): void
    {
        $this->stubMapping(
            path: '/tmp/mappings/pastimeevents/product_mappings.json',
            json: '{}',
            data: [],
        );

        $this->productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );
    }

    public function testProductIdsForReturnsProductIdKeyedByRequestedName(): void
    {
        $this->stubMapping(
            path: '/tmp/mappings/pastimeevents/product_mappings.json',
            json: '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688}}',
            data: [
                'Kamigawa Neon Dynasty Collector Booster' => [
                    'cardmarket_product_id' => 587688,
                    'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
                ],
            ],
        );

        $productIds = $this->productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 587688], $productIds);
    }

    public function testProductIdsForOmitsNamesMissingFromTheMapping(): void
    {
        $this->stubMapping(
            path: '/tmp/mappings/pastimeevents/product_mappings.json',
            json: '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688}}',
            data: [
                'Kamigawa Neon Dynasty Collector Booster' => ['cardmarket_product_id' => 587688],
            ],
        );

        $productIds = $this->productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster', 'Unknown Product'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 587688], $productIds);
    }

    public function testProductIdsForMatchesMappedNamesExactly(): void
    {
        $this->stubMapping(
            path: '/tmp/mappings/pastimeevents/product_mappings.json',
            json: '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688}}',
            data: [
                'Kamigawa Neon Dynasty Collector Booster' => ['cardmarket_product_id' => 587688],
            ],
        );

        $productIds = $this->productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['kamigawa neon dynasty collector booster'],
        );

        $this->assertSame([], $productIds);
    }

    public function testProductIdsForOmitsEntriesWithoutACardmarketProductId(): void
    {
        $this->stubMapping(
            path: '/tmp/mappings/pastimeevents/product_mappings.json',
            json: '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_name":"Kamigawa: Neon Dynasty Collector Booster"}}',
            data: [
                'Kamigawa Neon Dynasty Collector Booster' => [
                    'cardmarket_name' => 'Kamigawa: Neon Dynasty Collector Booster',
                ],
            ],
        );

        $productIds = $this->productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame([], $productIds);
    }

    public function testProductIdsForReturnsProductIdsAsIntegers(): void
    {
        $this->stubMapping(
            path: '/tmp/mappings/pastimeevents/product_mappings.json',
            json: '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":"587688"}}',
            data: [
                'Kamigawa Neon Dynasty Collector Booster' => ['cardmarket_product_id' => '587688'],
            ],
        );

        $productIds = $this->productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 587688], $productIds);
    }

    public function testProductIdsForReadsTheMappingOnceRegardlessOfTheNumberOfNames(): void
    {
        $this->stubMapping(
            path: '/tmp/mappings/pastimeevents/product_mappings.json',
            json: '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688},"Modern Horizons 3 Collector Booster":{"cardmarket_product_id":758481}}',
            data: [
                'Kamigawa Neon Dynasty Collector Booster' => ['cardmarket_product_id' => 587688],
                'Modern Horizons 3 Collector Booster'     => ['cardmarket_product_id' => 758481],
            ],
        );

        $productIds = $this->productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster', 'Modern Horizons 3 Collector Booster'],
        );

        $this->assertSame(
            [
                'Kamigawa Neon Dynasty Collector Booster' => 587688,
                'Modern Horizons 3 Collector Booster'     => 758481,
            ],
            $productIds,
        );
    }

    private function stubMapping(string $path, string $json, array $data): void
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
