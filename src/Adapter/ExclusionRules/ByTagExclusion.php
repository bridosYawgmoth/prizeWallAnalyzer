<?php

declare(strict_types=1);

namespace App\Adapter\ExclusionRules;

final class ByTagExclusion implements ExclusionRuleInterface
{
    /**
     * Accessory brands and product lines that carry no sealed-product value.
     */
    private const array DEFAULT_EXCLUDED_TAGS = [
        'Ultimate Guard',
        'HEAVY PLAY',
        'Playmat',
        'Sleeves',
        'Art Sleeves',
        'Deck Box',
        'Storage',
        'Binder',
        'Zipfolio',
        'Sidewinder',
        'Squaroe',
        "Flip'n'Tray",
        "Twin Flip'n'Tray",
        "Album'n'Case",
        'Boulder',
        'Katana',
        'Cortex',
        'Arkhive',
        'Oversized Card',
    ];

    /** @var array<string, true> */
    private readonly array $excludedTagKeys;

    /**
     * @param string[] $excludedTags tags that disqualify an item
     */
    public function __construct(array $excludedTags = self::DEFAULT_EXCLUDED_TAGS)
    {
        $this->excludedTagKeys = array_fill_keys(array_map($this->tagKey(...), array_filter($excludedTags)), true);
    }

    public function shouldSkipItem(string $value): bool
    {
        $key = $this->tagKey($value);

        return $key !== '' && isset($this->excludedTagKeys[$key]);
    }

    /**
     * The wall spells the same tag inconsistently, mixing "Deck Box"/"Deckbox" and "HEAVY PLAY"/"HeavyPlay".
     */
    private function tagKey(string $tag): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', mb_strtolower($tag));
    }
}
