<?php

namespace App\Tests\Cardmarket\OAuth;

use App\Cardmarket\Enum\HttpMethod;
use App\Cardmarket\OAuth\OAuthOneSigner;
use PHPUnit\Framework\TestCase;

final class OAuthOneSignerTest extends TestCase
{
    private OAuthOneSigner $signer;

    protected function setUp(): void
    {
        $this->signer = new OAuthOneSigner(
            appToken:          'testAppToken',
            appSecret:         'testAppSecret',
            accessToken:       'testAccessToken',
            accessTokenSecret: 'testAccessTokenSecret',
        );
    }

    public function testSignReturnsCorrectAuthorizationHeader(): void
    {
        $header = $this->signer->sign(
            method:    HttpMethod::GET,
            url:       'https://api.cardmarket.com/ws/v2.0/products/123',
            timestamp: 1700000000,
            nonce:     'abc123',
        );

        $expected = 'OAuth realm="https%3A%2F%2Fapi.cardmarket.com%2Fws%2Fv2.0%2Fproducts%2F123",oauth_consumer_key="testAppToken",oauth_nonce="abc123",oauth_signature="IFMW2aZBDQrjGqXUEAPw11tyr58%3D",oauth_signature_method="HMAC-SHA1",oauth_timestamp="1700000000",oauth_token="testAccessToken",oauth_version="1.0"';

        $this->assertSame($expected, $header);
    }

    public function testSigningAUrlWithQueryParamsWorks(): void
    {
        $header = $this->signer->sign(
            method:    HttpMethod::GET,
            url:       'https://api.cardmarket.com/ws/v2.0/products/find?search=Foo+Bar&idGame=1',
            timestamp: 1700000000,
            nonce:     'abc123',
        );

        // realm = bare URL without query string
        // query params are merged into the signature param collection but do NOT appear in the header
        $expected = 'OAuth realm="https%3A%2F%2Fapi.cardmarket.com%2Fws%2Fv2.0%2Fproducts%2Ffind",oauth_consumer_key="testAppToken",oauth_nonce="abc123",oauth_signature="zWIo83eEn5mJmSaHursEOd3GZ%2FM%3D",oauth_signature_method="HMAC-SHA1",oauth_timestamp="1700000000",oauth_token="testAccessToken",oauth_version="1.0"';

        $this->assertSame($expected, $header);
    }
}
