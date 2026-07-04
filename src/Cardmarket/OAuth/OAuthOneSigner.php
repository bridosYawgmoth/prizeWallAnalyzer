<?php

namespace App\Cardmarket\OAuth;

use App\Cardmarket\Enum\HttpMethod;

final class OAuthOneSigner implements OAuthSignerInterface
{
    public function __construct(
        private readonly string $appToken,
        private readonly string $appSecret,
        private readonly string $accessToken,
        private readonly string $accessTokenSecret,
    ) {
    }

    public function sign(HttpMethod $method, string $url, int $timestamp, string $nonce): string
    {
        $params    = $this->buildParams($timestamp, $nonce);
        $signature = $this->computeSignature(
            method: $method,
            url:    $url,
            params: $params,
        );

        $params['oauth_signature'] = $signature;
        ksort($params);

        return 'OAuth ' . implode(', ', $this->encodeHeaderParts($params));
    }

    /** @return array<string, string> */
    private function buildParams(int $timestamp, string $nonce): array
    {
        $params = [
            'oauth_consumer_key'     => $this->appToken,
            'oauth_nonce'            => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => (string) $timestamp,
            'oauth_token'            => $this->accessToken,
            'oauth_version'          => '1.0',
        ];

        ksort($params);

        return $params;
    }

    /** @param array<string, string> $params */
    private function computeSignature(HttpMethod $method, string $url, array $params): string
    {
        $paramString = implode('&', $this->encodeParamPairs($params));

        $baseString = $method->value
            . '&' . rawurlencode($url)
            . '&' . rawurlencode($paramString);

        $signingKey = rawurlencode($this->appSecret) . '&' . rawurlencode($this->accessTokenSecret);

        return base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
    }

    /** @param array<string, string> $params */
    private function encodeParamPairs(array $params): array
    {
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = rawurlencode($k) . '=' . rawurlencode($v);
        }

        return $pairs;
    }

    /** @param array<string, string> $params */
    private function encodeHeaderParts(array $params): array
    {
        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = rawurlencode($k) . '="' . rawurlencode($v) . '"';
        }

        return $parts;
    }
}
