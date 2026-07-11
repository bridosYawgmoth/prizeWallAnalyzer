<?php

namespace App\Cardmarket;

use App\Cardmarket\Enum\HttpMethod;
use App\Cardmarket\OAuth\OAuthSignerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CardmarketClient implements CardmarketClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OAuthSignerInterface $signer,
    ) {
    }

    public function get(string $url): array
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
