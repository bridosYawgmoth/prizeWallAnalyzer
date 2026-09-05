<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Cardmarket\CardmarketClientInterface;
use App\Service\CardmarketTrendPriceProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CardmarketTrendPriceProviderTest extends TestCase
{
    private CardmarketClientInterface&MockObject $cardmarketClient;
    private CardmarketTrendPriceProvider $trendPriceProvider;

    protected function setUp(): void
    {
        $this->cardmarketClient   = $this->createMock(CardmarketClientInterface::class);
        $this->trendPriceProvider = new CardmarketTrendPriceProvider($this->cardmarketClient);
    }

    public function testTrendPriceForReturnsTheTrendPriceOfTheRequestedProduct(): void
    {
        $this->stubProduct(
            productId: 587688,
            response: [
                'product' => [
                    'idProduct'  => 587688,
                    'priceGuide' => ['TREND' => 18.50],
                ],
            ],
        );

        $this->assertSame(18.50, $this->trendPriceProvider->trendPriceFor(587688));
    }

    public function testTrendPriceForReturnsTheTrendPriceAsFloat(): void
    {
        $this->stubProduct(
            productId: 587688,
            response: ['product' => ['priceGuide' => ['TREND' => '18.50']]],
        );

        $this->assertSame(18.50, $this->trendPriceProvider->trendPriceFor(587688));
    }

    public function testTrendPriceForReturnsNullWhenThePriceGuideHasNoTrend(): void
    {
        $this->stubProduct(
            productId: 587688,
            response: [
                'product' => [
                    'idProduct'  => 587688,
                    'priceGuide' => [],
                ],
            ],
        );

        $this->assertNull($this->trendPriceProvider->trendPriceFor(587688));
    }

    public function testTrendPriceForReturnsNullWhenTheResponseHasNoProduct(): void
    {
        $this->stubProduct(productId: 587688, response: []);

        $this->assertNull($this->trendPriceProvider->trendPriceFor(587688));
    }

    public function testTrendPriceForReturnsNullWhenCardmarketReturnsAnErrorPayload(): void
    {
        $this->stubProduct(
            productId: 587688,
            response: ['errors' => [['message' => 'No product found', 'code' => 404]]],
        );

        $this->assertNull($this->trendPriceProvider->trendPriceFor(587688));
    }

    private function stubProduct(int $productId, array $response): void
    {
        $this->cardmarketClient
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willReturn($response);
    }
}
