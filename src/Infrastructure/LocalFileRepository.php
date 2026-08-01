<?php

declare(strict_types=1);

namespace App\Infrastructure;

use RuntimeException;

final class LocalFileRepository implements FileReaderRepositoryInterface
{
    /** @codeCoverageIgnore */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * @codeCoverageIgnore
     * @throws RuntimeException if the file cannot be read
     */
    public function read(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException(sprintf('Failed to read file: %s', $path));
        }

        return $content;
    }

    /**
     * @codeCoverageIgnore
     * @throws RuntimeException if the file cannot be written
     */
    public function write(string $path, string $content): void
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir(directory: $dir, permissions: 0775, recursive: true);
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException(sprintf('Failed to write file: %s', $path));
        }
    }
}
