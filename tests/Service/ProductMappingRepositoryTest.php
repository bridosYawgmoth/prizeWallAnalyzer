<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Infrastructure\FileReaderRepositoryInterface;
use App\Infrastructure\JsonParserInterface;
use App\Service\ProductMappingRepository;
use PHPUnit\Framework\TestCase;

final class ProductMappingRepositoryTest extends TestCase
{
    private const string MAPPING_DIR = '/tmp/mappings';

    public function testProductIdsForReadsTheMappingFileOfTheOrganizer(): void
    {
        $fileReader = $this->createMock(FileReaderRepositoryInterface::class);
        $fileReader->method('exists')->willReturn(true);
        $fileReader
            ->expects($this->once())
            ->method('read')
            ->with(self::MAPPING_DIR . '/pastimeevents/product_mappings.json')
            ->willReturn('{}');

        $productMappings = new ProductMappingRepository(
            fileReader: $fileReader,
            jsonParser: $this->jsonParserReturning([]),
            mappingDir: self::MAPPING_DIR,
        );

        $productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );
    }

    public function testProductIdsForReadsTheMappingOnceRegardlessOfTheNumberOfNames(): void
    {
        $json = '{"Kamigawa Neon Dynasty Collector Booster":{"cardmarket_product_id":587688}}';

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
            ->willReturn([
                'Kamigawa Neon Dynasty Collector Booster' => ['cardmarket_product_id' => 587688],
            ]);

        $productMappings = new ProductMappingRepository(
            fileReader: $fileReader,
            jsonParser: $jsonParser,
            mappingDir: self::MAPPING_DIR,
        );

        $productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: [
                'Kamigawa Neon Dynasty Collector Booster',
                'Modern Horizons 3 Collector Booster',
                'Bloomburrow Play Booster',
                'Foundations Jumpstart Booster',
            ],
        );
    }

    public function testProductIdsForReturnsNoProductIdsWhenTheMappingFileDoesNotExist(): void
    {
        $fileReader = $this->createMock(FileReaderRepositoryInterface::class);
        $fileReader
            ->expects($this->once())
            ->method('exists')
            ->with(self::MAPPING_DIR . '/unknownorganizer/product_mappings.json')
            ->willReturn(false);
        $fileReader
            ->expects($this->never())
            ->method('read');

        $productMappings = new ProductMappingRepository(
            fileReader: $fileReader,
            jsonParser: $this->jsonParserReturning([]),
            mappingDir: self::MAPPING_DIR,
        );

        $productIds = $productMappings->productIdsFor(
            organizer: 'unknownorganizer',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame([], $productIds);
    }

    public function testProductIdsForReturnsProductIdKeyedByRequestedName(): void
    {
        $productMappings = $this->productMappingsContaining([
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_product_id' => 587688,
                'cardmarket_name'       => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
        ]);

        $productIds = $productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 587688], $productIds);
    }

    public function testProductIdsForOmitsNamesMissingFromTheMapping(): void
    {
        $productMappings = $this->productMappingsContaining([
            'Kamigawa Neon Dynasty Collector Booster' => ['cardmarket_product_id' => 587688],
        ]);

        $productIds = $productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster', 'Unknown Product'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 587688], $productIds);
    }

    public function testProductIdsForMatchesMappedNamesExactly(): void
    {
        $productMappings = $this->productMappingsContaining([
            'Kamigawa Neon Dynasty Collector Booster' => ['cardmarket_product_id' => 587688],
        ]);

        $productIds = $productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['kamigawa neon dynasty collector booster'],
        );

        $this->assertSame([], $productIds);
    }

    public function testProductIdsForOmitsEntriesWithoutACardmarketProductId(): void
    {
        $productMappings = $this->productMappingsContaining([
            'Kamigawa Neon Dynasty Collector Booster' => [
                'cardmarket_name' => 'Kamigawa: Neon Dynasty Collector Booster',
            ],
        ]);

        $productIds = $productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame([], $productIds);
    }

    public function testProductIdsForReturnsProductIdsAsIntegers(): void
    {
        $productMappings = $this->productMappingsContaining([
            'Kamigawa Neon Dynasty Collector Booster' => ['cardmarket_product_id' => '587688'],
        ]);

        $productIds = $productMappings->productIdsFor(
            organizer: 'pastimeevents',
            names: ['Kamigawa Neon Dynasty Collector Booster'],
        );

        $this->assertSame(['Kamigawa Neon Dynasty Collector Booster' => 587688], $productIds);
    }

    /**
     * @param array<string, array<string, mixed>> $mapping decoded mapping contents, keyed by exact name
     */
    private function productMappingsContaining(array $mapping): ProductMappingRepository
    {
        $fileReader = $this->createStub(FileReaderRepositoryInterface::class);
        $fileReader->method('exists')->willReturn(true);
        $fileReader->method('read')->willReturn('{}');

        return new ProductMappingRepository(
            fileReader: $fileReader,
            jsonParser: $this->jsonParserReturning($mapping),
            mappingDir: self::MAPPING_DIR,
        );
    }

    /**
     * @param array<string, array<string, mixed>> $data
     */
    private function jsonParserReturning(array $data): JsonParserInterface
    {
        $jsonParser = $this->createStub(JsonParserInterface::class);
        $jsonParser->method('decode')->willReturn($data);

        return $jsonParser;
    }
}
