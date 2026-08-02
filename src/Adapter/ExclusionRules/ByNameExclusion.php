<?php

declare(strict_types=1);

namespace App\Adapter\ExclusionRules;

final class ByNameExclusion implements ExclusionRuleInterface
{
    /**
     * Accessory lines the wall does not tag reliably, so they have to be caught on the name instead.
     */
    public const array DEFAULT_EXCLUDED_NAMES = [
        'Sleeves',
        'Xenoskin',
        'Sidewinder',
        'Portfolio',
        'Flexxfolio',
        'Toploading',
        'Card Covers',
        'Water Bottle',
    ];

    /** @var string[] */
    private readonly array $excludedNames;

    /**
     * @param string[] $excludedNames substrings that disqualify an item
     */
    public function __construct(array $excludedNames = self::DEFAULT_EXCLUDED_NAMES)
    {
        $this->excludedNames = array_map(mb_strtolower(...), array_filter($excludedNames));
    }

    public function shouldSkipItem(string $value): bool
    {
        $haystack = mb_strtolower($value);

        foreach ($this->excludedNames as $excludedName) {
            if (str_contains($haystack, $excludedName)) {
                return true;
            }
        }

        return false;
    }
}
