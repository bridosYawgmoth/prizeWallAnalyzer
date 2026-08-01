<?php

declare(strict_types=1);

namespace App\Infrastructure;

interface JsonParserInterface
{
    /** @throws \JsonException if the string cannot be decoded */
    public function decode(string $json): array;

    /** @throws \JsonException if the data cannot be encoded */
    public function encode(array $data): string;
}
