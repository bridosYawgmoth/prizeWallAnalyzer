<?php

namespace App\Tests\Dto;

use App\Dto\PrizeWallItem;
use PHPUnit\Framework\TestCase;

final class PrizeWallItemTest extends TestCase
{
    public function testHasName(): void
    {
        $item = new PrizeWallItem(
            name: 'Play Booster Box',
            tixPrice: 120,
        );

        $this->assertSame('Play Booster Box', $item->name);
    }

    public function testHasTixPrice(): void
    {
        $item = new PrizeWallItem(
            name: 'Play Booster Box',
            tixPrice: 120,
        );

        $this->assertSame(120, $item->tixPrice);
    }

    public function testEurPriceIsNullByDefault(): void
    {
        $item = new PrizeWallItem(
            name: 'Play Booster Box',
            tixPrice: 120,
        );

        $this->assertNull($item->eurPrice);
    }

    public function testEurPriceCanBeSet(): void
    {
        $item = new PrizeWallItem(
            name: 'Play Booster Box',
            tixPrice: 120,
        );

        $item->eurPrice = 85.50;

        $this->assertSame(85.50, $item->eurPrice);
    }
}
