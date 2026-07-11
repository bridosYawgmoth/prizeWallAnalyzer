<?php

declare(strict_types=1);

namespace App\Infrastructure;

final class JsonParser implements JsonParserInterface
{
    /**
     * @codeCoverageIgnore
     * @throws \JsonException if the string cannot be decoded
     */
    public function decode(string $json): array
    {
        return json_decode(json: $json, associative: true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @codeCoverageIgnore
     * @throws \JsonException if the data cannot be encoded
     */
    public function encode(array $data): string
    {
        return json_encode(value: $data, flags: JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }
}
