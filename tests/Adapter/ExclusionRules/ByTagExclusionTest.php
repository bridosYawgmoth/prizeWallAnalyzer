<?php

declare(strict_types=1);

namespace App\Tests\Adapter\ExclusionRules;

use App\Adapter\ExclusionRules\ByTagExclusion;
use App\Adapter\ExclusionRules\ExclusionRuleInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class ByTagExclusionTest extends TestCase
{
    private ByTagExclusion $rule;

    protected function setUp(): void
    {
        $this->rule = new ByTagExclusion();
    }

    public function testIsAnExclusionRule(): void
    {
        $this->assertInstanceOf(ExclusionRuleInterface::class, $this->rule);
    }

    #[TestWith(['tag' => 'Ultimate Guard'], 'Accessory brand is excluded')]
    #[TestWith(['tag' => 'Playmat'], 'Playmat is excluded')]
    #[TestWith(['tag' => 'Deck Box'], 'Deck Box is excluded')]
    #[TestWith(['tag' => 'Storage'], 'Storage is excluded')]
    public function testExcludesAccessoryTags(string $tag): void
    {
        $this->assertTrue($this->rule->shouldSkipItem($tag));
    }

    #[TestWith(['tag' => 'Deckbox'], 'Missing space still matches Deck Box')]
    #[TestWith(['tag' => 'deck box'], 'Lowercase still matches Deck Box')]
    #[TestWith(['tag' => 'HeavyPlay'], 'Mixed casing still matches HEAVY PLAY')]
    #[TestWith(['tag' => 'heavy play'], 'Lowercase with space still matches HEAVY PLAY')]
    #[TestWith(['tag' => "Album'n'Case"], 'Punctuation is ignored when matching')]
    #[TestWith(['tag' => 'Album n Case'], 'Punctuation replaced by spaces still matches')]
    public function testTagMatchingIgnoresCasingSpacingAndPunctuation(string $tag): void
    {
        $this->assertTrue($this->rule->shouldSkipItem($tag));
    }

    #[TestWith(['tag' => 'Booster'], 'Booster is kept')]
    #[TestWith(['tag' => 'Magic: the Gathering'], 'Magic: the Gathering is kept')]
    #[TestWith(['tag' => 'Commander Deck'], 'Commander Deck is kept')]
    #[TestWith(['tag' => 'Secret Lair'], 'Secret Lair is kept')]
    #[TestWith(['tag' => 'Bundle'], 'Bundle is kept')]
    #[TestWith(['tag' => ''], 'Empty tag is kept')]
    public function testKeepsSealedProductTags(string $tag): void
    {
        $this->assertFalse($this->rule->shouldSkipItem($tag));
    }

    public function testExcludedTagsCanBeReplaced(): void
    {
        $rule = new ByTagExclusion(['Booster']);

        $this->assertTrue($rule->shouldSkipItem('Booster'));
        $this->assertFalse($rule->shouldSkipItem('Ultimate Guard'));
    }

    public function testEmptyListExcludesNothing(): void
    {
        $rule = new ByTagExclusion([]);

        $this->assertFalse($rule->shouldSkipItem('Ultimate Guard'));
    }

    public function testPartialTagMatchesAreNotExcluded(): void
    {
        $this->assertFalse($this->rule->shouldSkipItem('Ultimate Guard Portfolio 480'));
    }
}
