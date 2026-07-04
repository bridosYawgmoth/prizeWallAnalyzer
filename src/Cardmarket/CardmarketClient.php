<?php

namespace App\Cardmarket;

use App\Cardmarket\OAuth\OAuthOneSigner;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CardmarketClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OAuthOneSigner $signer,
    ) {
    }
}
