<?php

declare(strict_types=1);

namespace App\Infrastructure;

interface FileReaderRepositoryInterface
{
    public function exists(string $path): bool;

    /** @throws \RuntimeException if the file cannot be read */
    public function read(string $path): string;

    /** @throws \RuntimeException if the file cannot be written */
    public function write(string $path, string $content): void;
}
