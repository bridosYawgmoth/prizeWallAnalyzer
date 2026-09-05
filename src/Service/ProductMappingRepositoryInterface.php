<?php

declare(strict_types=1);

namespace App\Service;

interface ProductMappingRepositoryInterface
{
    /**
     * @param string[] $names
     * @return array<string, int> Cardmarket product ids keyed by the requested name, omitting unmapped names
     */
    public function productIdsFor(string $organizer, array $names): array;
}
