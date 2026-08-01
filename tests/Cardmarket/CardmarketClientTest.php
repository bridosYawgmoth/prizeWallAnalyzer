<?php

namespace App\Tests\Cardmarket;

use App\Cardmarket\CardmarketClient;
use App\Cardmarket\Enum\HttpMethod;
use App\Cardmarket\OAuth\OAuthSignerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class CardmarketClientTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private OAuthSignerInterface&MockObject $signer;
    private CardmarketClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->signer     = $this->createMock(OAuthSignerInterface::class);
        $this->client     = new CardmarketClient($this->httpClient, $this->signer);
    }

    public function testGetProductReturnsDecodedJsonArray(): void
    {
        $url        = 'https://apiv2.cardmarket.com/ws/v2.0/output.json/products/1';
        $authHeader = 'OAuth oauth_consumer_key="testAppToken"';

        $expectedResponse = [
            'product' => [
                'idProduct'     => 1,
                'idMetaproduct' => 1,
                'enName'        => 'Black Lotus',
                'gameName'      => 'Magic the Gathering',
                'categoryName'  => 'Magic Single',
                'expansionName' => 'Alpha',
                'rarity'        => 'Rare',
                'priceGuide'    => [
                    'SELL'      => 50000.0,
                    'LOW'       => 48000.0,
                    'LOWEX+'    => 49000.0,
                    'LOWFOIL'   => 0.0,
                    'AVG'       => 51000.0,
                    'TREND'     => 50500.0,
                    'TRENDFOIL' => 0.0,
                ],
            ],
        ];

        $this->signer
            ->expects($this->once())
            ->method('sign')
            ->with(HttpMethod::GET, $url, $this->isInt(), $this->isString())
            ->willReturn($authHeader);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn($expectedResponse);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(HttpMethod::GET->value, $url, $this->callback(fn($options) => $options['headers']['Authorization'] === $authHeader))
            ->willReturn($response);

        $result = $this->client->getProduct(productId: 1);

        $this->assertSame($expectedResponse, $result);
        $this->assertSame('Black Lotus', $result['product']['enName']);
        $this->assertSame(50500.0, $result['product']['priceGuide']['TREND']);
    }

    public function testGetProductReturnsEmptyArrayWhenTransportFails(): void
    {
        $this->signer
            ->expects($this->once())
            ->method('sign')
            ->willReturn('OAuth oauth_consumer_key="testAppToken"');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new TransportException('Connection timed out'));

        $this->assertSame([], $this->client->getProduct(productId: 1));
    }

    public function testGetProductReturnsEmptyArrayWhenResponseCannotBeDecoded(): void
    {
        $this->signer
            ->expects($this->once())
            ->method('sign')
            ->willReturn('OAuth oauth_consumer_key="testAppToken"');

        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willThrowException(new JsonException('Invalid JSON'));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->assertSame([], $this->client->getProduct(productId: 1));
    }
}
