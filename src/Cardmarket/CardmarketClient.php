<?php

namespace App\Cardmarket;

use App\Cardmarket\Enum\HttpMethod;
use App\Cardmarket\OAuth\OAuthSignerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CardmarketClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OAuthSignerInterface $signer,
    ) {
    }

    public function get(string $url): array
    {
        $authHeader = $this->signer->sign(HttpMethod::GET, $url, time(), bin2hex(random_bytes(16)));

        $response = $this->httpClient->request(HttpMethod::GET->value, $url, [
            'headers' => ['Authorization' => $authHeader],
        ]);

        return $response->toArray();
    }
}
