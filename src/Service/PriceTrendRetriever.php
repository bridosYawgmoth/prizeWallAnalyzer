<?php

declare(strict_types=1);

namespace App\Service;

use App\Cardmarket\CardmarketClientInterface;
use App\Dto\PrizeWallItem;
use App\Infrastructure\FileReaderRepositoryInterface;
use App\Infrastructure\JsonParserInterface;

class PriceTrendRetriever
{
    public function __construct(
        private readonly CardmarketClientInterface $cardmarketClient,
        private readonly FileReaderRepositoryInterface $fileReader,
        private readonly JsonParserInterface $jsonParser,
        private readonly string $cacheDir,
    ) {
    }

    /**
     * @param PrizeWallItem[] $items
     * @return PrizeWallItem[]
     */
    public function getPrices(string $organizer, array $items): array
    {
        $cachePath = sprintf('%s/%s.json', $this->cacheDir, $organizer);
        $cache     = $this->jsonParser->decode($this->fileReader->read($cachePath));

        foreach ($items as $item) {
            $key = strtolower(trim($item->name));

            if (isset($cache[$key])) {
                $item->eurPrice = (float) $cache[$key];
            }
        }

        return $items;
    }
}
