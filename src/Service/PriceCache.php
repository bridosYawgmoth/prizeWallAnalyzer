<?php

declare(strict_types=1);

namespace App\Service;

use App\Infrastructure\FileReaderRepositoryInterface;
use App\Infrastructure\JsonParserInterface;

final class PriceCache implements PriceCacheInterface
{
    public function __construct(
        private readonly FileReaderRepositoryInterface $fileReader,
        private readonly JsonParserInterface $jsonParser,
        private readonly string $cacheDir,
    ) {
    }

    /** @inheritDoc */
    public function pricesFor(string $organizer, array $names): array
    {
        $cache  = $this->read($organizer);
        $prices = [];

        foreach ($names as $name) {
            $key = $this->normalizeName($name);

            if (!isset($cache[$key])) {
                continue;
            }

            $prices[$name] = (float) $cache[$key];
        }

        return $prices;
    }

    private function read(string $organizer): array
    {
        $path = sprintf('%s/%s.json', $this->cacheDir, $organizer);

        return $this->jsonParser->decode($this->fileReader->read($path));
    }

    private function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }
}
