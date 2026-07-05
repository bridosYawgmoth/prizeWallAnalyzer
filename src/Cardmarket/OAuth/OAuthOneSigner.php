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
        $baseUrl     = $this->extractBaseUrl($url);
        $queryParams = $this->extractQueryParams($url);

        $params    = $this->buildParams($timestamp, $nonce);
        $signature = $this->computeSignature(
            method:  $method,
            baseUrl: $baseUrl,
            params:  array_merge($params, $queryParams),
        );

        $params['oauth_signature'] = $signature;
        ksort($params);

        $headerParts = ['realm="' . rawurlencode($baseUrl) . '"'];
        foreach ($this->encodeHeaderParts($params) as $part) {
            $headerParts[] = $part;
        }

        return 'OAuth ' . implode(',', $headerParts);
    }

    private function extractBaseUrl(string $url): string
    {
        $parts = parse_url($url);

        return $parts['scheme'] . '://' . $parts['host'] . ($parts['path'] ?? '');
    }

    /** @return array<string, string> */
    private function extractQueryParams(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query === null || $query === false || $query === '') {
            return [];
        }

        parse_str($query, $params);

        return array_map('strval', $params);
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
    private function computeSignature(HttpMethod $method, string $baseUrl, array $params): string
    {
        ksort($params);
        $paramString = implode('&', $this->encodeParamPairs($params));

        $baseString = $method->value
            . '&' . rawurlencode($baseUrl)
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
