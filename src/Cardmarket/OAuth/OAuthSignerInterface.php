<?php

namespace App\Cardmarket\OAuth;

interface OAuthSignerInterface
{
    public function sign(string $method, string $url, int $timestamp, string $nonce): string;
}
