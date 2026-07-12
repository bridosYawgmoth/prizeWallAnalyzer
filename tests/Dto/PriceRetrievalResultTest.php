<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\PriceRetrievalResult;
use App\Dto\PrizeWallItem;
use PHPUnit\Framework\TestCase;

final class PriceRetrievalResultTest extends TestCase
{
    public function testStoresFoundAndNotFoundItems(): void
    {
        $found    = [new PrizeWallItem(name: 'Play Booster Box', tixPrice: 120)];
        $notFound = [new PrizeWallItem(name: 'Unknown Product', tixPrice: 50)];

        $result = new PriceRetrievalResult(
            prizeWallItems:         $found,
            prizeWallItemsNotFound: $notFound,
        );

        $this->assertSame($found, $result->prizeWallItems);
        $this->assertSame($notFound, $result->prizeWallItemsNotFound);
    }
}
