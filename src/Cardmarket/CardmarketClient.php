<?php

namespace App\Cardmarket;

use App\Cardmarket\Enum\HttpMethod;
use App\Cardmarket\OAuth\OAuthSignerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CardmarketClient implements CardmarketClientInterface
{
    private const string BASE_URL = 'https://apiv2.cardmarket.com/ws/v2.0/output.json/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OAuthSignerInterface $signer,
    ) {
    }

    public function getProduct(int $productId): array
    {
        $url = sprintf('%sproducts/%d', self::BASE_URL, $productId);

        return $this->get($url);
    }

    private function get(string $url): array
    {
        $authHeader = $this->signer->sign(
            method:    HttpMethod::GET,
            url:       $url,
            timestamp: time(),
            nonce:     bin2hex(random_bytes(16)),
        );

        $response = $this->httpClient->request(
            method:  HttpMethod::GET->value,
            url:     $url,
            options: ['headers' => ['Authorization' => $authHeader]],
        );

        return $response->toArray();
    }
}
