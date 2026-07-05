<?php

namespace App\Cardmarket\OAuth;

use App\Cardmarket\Enum\HttpMethod;

interface OAuthSignerInterface
{
    public function sign(HttpMethod $method, string $url, int $timestamp, string $nonce): string;
}
