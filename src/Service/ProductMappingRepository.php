<?php

declare(strict_types=1);

namespace App\Service;

use App\Infrastructure\FileReaderRepositoryInterface;
use App\Infrastructure\JsonParserInterface;

final class ProductMappingRepository implements ProductMappingRepositoryInterface
{
    private const string PRODUCT_ID_KEY = 'cardmarket_product_id';

    public function __construct(
        private readonly FileReaderRepositoryInterface $fileReader,
        private readonly JsonParserInterface $jsonParser,
        private readonly string $mappingDir,
    ) {
    }

    /** @inheritDoc */
    public function productIdsFor(string $organizer, array $names): array
    {
        $mapping    = $this->read($organizer);
        $productIds = [];

        foreach ($names as $name) {
            if (!isset($mapping[$name][self::PRODUCT_ID_KEY])) {
                continue;
            }

            $productIds[$name] = (int) $mapping[$name][self::PRODUCT_ID_KEY];
        }

        return $productIds;
    }

    private function read(string $organizer): array
    {
        $path = sprintf('%s/%s/product_mappings.json', $this->mappingDir, $organizer);

        return $this->jsonParser->decode($this->fileReader->read($path));
    }
}
