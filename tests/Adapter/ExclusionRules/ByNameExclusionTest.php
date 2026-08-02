<?php

declare(strict_types=1);

namespace App\Tests\Adapter\ExclusionRules;

use App\Adapter\ExclusionRules\ByNameExclusion;
use App\Adapter\ExclusionRules\ExclusionRuleInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class ByNameExclusionTest extends TestCase
{
    private ByNameExclusion $rule;

    protected function setUp(): void
    {
        $this->rule = new ByNameExclusion();
    }

    public function testIsAnExclusionRule(): void
    {
        $this->assertInstanceOf(ExclusionRuleInterface::class, $this->rule);
    }

    #[TestWith(['name' => 'Sidewinder 133+ Xenoskin - Purple'], 'Deck case is excluded')]
    #[TestWith(['name' => 'Ultimate Guard Portfolio 480 24-Pocket Xenoskin - Black'], 'Portfolio is excluded')]
    #[TestWith(['name' => 'Katana Sleeves – Standard Size – Black'], 'Sleeves are excluded')]
    #[TestWith(['name' => 'Precise-Fit 100 Resealable Sleeves'], 'Resealable sleeves are excluded')]
    #[TestWith(['name' => 'Card Covers Toploading (25) - 35 pt'], 'Card covers are excluded')]
    #[TestWith(['name' => 'Flexxfolio 360 18-Pocket Xenoskin - Black - Ultimate Guard'], 'Flexxfolio is excluded')]
    #[TestWith(['name' => 'Pokémon Go Water Bottle – Team Instinct'], 'Non card product is excluded')]
    public function testExcludesAccessoryNames(string $name): void
    {
        $this->assertTrue($this->rule->shouldSkipItem($name));
    }

    #[TestWith(['name' => 'sidewinder 133+ xenoskin - purple'], 'Lowercase name still matches')]
    #[TestWith(['name' => 'KATANA SLEEVES – STANDARD SIZE – BLACK'], 'Uppercase name still matches')]
    public function testNameMatchingIgnoresCasing(string $name): void
    {
        $this->assertTrue($this->rule->shouldSkipItem($name));
    }

    #[TestWith(['name' => 'Play Booster - Final Fantasy'], 'Booster pack is kept')]
    #[TestWith(['name' => 'Collector Booster Box - Modern Horizons 3'], 'Derived booster box is kept')]
    #[TestWith(['name' => 'Commander Deck MH3: "Eldrazi Incursion"'], 'Commander deck is kept')]
    #[TestWith(['name' => 'Secret Lair - Tragic Romance - Regular'], 'Secret lair is kept')]
    #[TestWith(['name' => 'Bundle - Aetherdrift "Finish Line"'], 'Bundle is kept')]
    #[TestWith(['name' => 'Album’n’Case - Artist Edition #1 - MOH – Spirits of the Sea'], 'Name without an accessory keyword is kept')]
    #[TestWith(['name' => ''], 'Empty name is kept')]
    public function testKeepsSealedProductNames(string $name): void
    {
        $this->assertFalse($this->rule->shouldSkipItem($name));
    }

    public function testExcludedNamesCanBeReplaced(): void
    {
        $rule = new ByNameExclusion(['Booster']);

        $this->assertTrue($rule->shouldSkipItem('Play Booster - Final Fantasy'));
        $this->assertFalse($rule->shouldSkipItem('Katana Sleeves – Standard Size – Black'));
    }

    public function testEmptyListExcludesNothing(): void
    {
        $rule = new ByNameExclusion([]);

        $this->assertFalse($rule->shouldSkipItem('Katana Sleeves – Standard Size – Black'));
    }
}
