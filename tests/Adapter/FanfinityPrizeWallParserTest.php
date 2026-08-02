<?php

declare(strict_types=1);

namespace App\Tests\Adapter;

use App\Adapter\ExclusionRules\ByNameExclusion;
use App\Adapter\ExclusionRules\ByTagExclusion;
use App\Adapter\FanfinityPrizeWallParser;
use App\Dto\PrizeWallItem;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class FanfinityPrizeWallParserTest extends TestCase
{
    private FanfinityPrizeWallParser $parser;

    protected function setUp(): void
    {
        $this->parser = new FanfinityPrizeWallParser();
    }

    private function fixture(): string
    {
        return file_get_contents(__DIR__ . '/../Fixtures/fanfinity_prizewall.html');
    }

    /**
     * @param PrizeWallItem[] $items
     * @return string[]
     */
    private function namesOf(array $items): array
    {
        return array_map(static fn (PrizeWallItem $item): string => $item->name, $items);
    }

    public function testReturnsOnlyIncludedAndDerivedItems(): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertCount(11, $items);
        $this->assertContainsOnlyInstancesOf(PrizeWallItem::class, $items);
    }

    #[TestWith(['index' => 0, 'expectedName' => 'Play Booster - Final Fantasy', 'expectedTixPrice' => 80], 'Play booster pack is parsed')]
    #[TestWith(['index' => 2, 'expectedName' => 'Collector Booster - Modern Horizons 3', 'expectedTixPrice' => 1200], 'Collector booster pack is parsed')]
    #[TestWith(['index' => 4, 'expectedName' => 'Jumpstart Booster - Avatar: The Last Airbender', 'expectedTixPrice' => 80], 'Jumpstart booster pack is parsed')]
    #[TestWith(['index' => 5, 'expectedName' => 'Bundle - Aetherdrift "Finish Line"', 'expectedTixPrice' => 900], 'Double quotes in the name survive parsing')]
    #[TestWith(['index' => 7, 'expectedName' => 'Magic: The Gathering® | Teenage Mutant Ninja Turtles Turtle Team-Up', 'expectedTixPrice' => 700], 'Registered trademark sign and pipe survive parsing')]
    #[TestWith(['index' => 8, 'expectedName' => 'Prerelease Kit - Lorwyn Eclipsed', 'expectedTixPrice' => 250], 'Item without any tags is kept')]
    public function testParsesNameAndTixPrice(int $index, string $expectedName, int $expectedTixPrice): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertSame($expectedName, $items[$index]->name);
        $this->assertSame($expectedTixPrice, $items[$index]->tixPrice);
    }

    #[TestWith(['index' => 1, 'expectedName' => 'Play Booster Box - Final Fantasy', 'expectedTixPrice' => 2400], 'Play booster box is 30 packs')]
    #[TestWith(['index' => 3, 'expectedName' => 'Collector Booster Box - Modern Horizons 3', 'expectedTixPrice' => 14400], 'Collector booster box is 12 packs')]
    public function testDerivesBoosterBoxDirectlyAfterItsPack(int $index, string $expectedName, int $expectedTixPrice): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertSame($expectedName, $items[$index]->name);
        $this->assertSame($expectedTixPrice, $items[$index]->tixPrice);
    }

    #[TestWith(['notDerived' => 'Jumpstart Booster Box - Avatar: The Last Airbender'], 'Jumpstart boosters get no box')]
    #[TestWith(['notDerived' => 'Bundle Box - Aetherdrift "Finish Line"'], 'Bundles get no box')]
    #[TestWith(['notDerived' => 'Prerelease Kit Box - Lorwyn Eclipsed'], 'Prerelease kits get no box')]
    public function testDoesNotDeriveBoxesForNonPlayAndNonCollectorBoosters(string $notDerived): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertNotContains($notDerived, $this->namesOf($items));
    }

    #[TestWith(['excludedName' => 'Album’n’Case - Artist Edition #1 - MOH – Spirits of the Sea'], 'Ultimate Guard deck box is excluded')]
    #[TestWith(['excludedName' => 'Arkhive 1000+ Xenoskin Magic: The Gathering "Guild Summit"'], 'Storage item is excluded')]
    public function testExcludesAccessoryItemsByTag(string $excludedName): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertNotContains($excludedName, $this->namesOf($items));
    }

    #[TestWith(['excludedName' => 'Katana Sleeves – Standard Size – Black'], 'Deckbox matches the Deck Box exclusion despite spacing')]
    #[TestWith(['excludedName' => 'Playmat - Marvel Super Heroes'], 'HeavyPlay matches the HEAVY PLAY exclusion despite casing')]
    public function testTagExclusionIgnoresCasingAndSpacing(string $excludedName): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertNotContains($excludedName, $this->namesOf($items));
    }

    #[TestWith(['excludedName' => 'Sidewinder 133+ Xenoskin - Purple'], 'Deck case tagged only with a colour is excluded by name')]
    #[TestWith(['excludedName' => 'Ultimate Guard Portfolio 480 24-Pocket Xenoskin - Black'], 'Untagged accessory is excluded by name')]
    #[TestWith(['excludedName' => 'Pokémon Go Water Bottle – Team Instinct'], 'Non card product is excluded by name')]
    public function testExcludesAccessoryItemsByNamePattern(string $excludedName): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertNotContains($excludedName, $this->namesOf($items));
    }

    public function testNamePatternMatchingIgnoresCasing(): void
    {
        $parser = new FanfinityPrizeWallParser(
            nameExclusion: new ByNameExclusion(['kAtAnA']),
            tagExclusion:  new ByTagExclusion([]),
        );

        $names = $this->namesOf($parser->parse($this->fixture()));

        $this->assertNotContains('Katana Sleeves – Standard Size – Black', $names);
        $this->assertContains('Sidewinder 133+ Xenoskin - Purple', $names);
    }

    public function testTagExclusionRuleCanBeReplacedThroughTheConstructor(): void
    {
        $parser = new FanfinityPrizeWallParser(tagExclusion: new ByTagExclusion(['Booster']));

        $names = $this->namesOf($parser->parse($this->fixture()));

        $this->assertNotContains('Play Booster - Final Fantasy', $names);
        $this->assertContains('Album’n’Case - Artist Edition #1 - MOH – Spirits of the Sea', $names);
    }

    public function testNameExclusionRuleCanBeReplacedThroughTheConstructor(): void
    {
        $parser = new FanfinityPrizeWallParser(nameExclusion: new ByNameExclusion([]));

        $names = $this->namesOf($parser->parse($this->fixture()));

        $this->assertContains('Pokémon Go Water Bottle – Team Instinct', $names);
        $this->assertContains('Sidewinder 133+ Xenoskin - Purple', $names);
    }

    public function testDisablingTagExclusionStillAppliesNameExclusion(): void
    {
        $parser = new FanfinityPrizeWallParser(tagExclusion: new ByTagExclusion([]));

        $names = $this->namesOf($parser->parse($this->fixture()));

        $this->assertCount(13, $names);
        $this->assertContains('Album’n’Case - Artist Edition #1 - MOH – Spirits of the Sea', $names);
        $this->assertNotContains('Sidewinder 133+ Xenoskin - Purple', $names);
    }

    public function testDroppingBothRulesKeepsEveryWellFormedItem(): void
    {
        $parser = new FanfinityPrizeWallParser(
            nameExclusion: new ByNameExclusion([]),
            tagExclusion:  new ByTagExclusion([]),
        );

        $this->assertCount(18, $parser->parse($this->fixture()));
    }

    public function testCollapsesWhitespaceInNames(): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertSame('Bundle - March of the Machine', $items[6]->name);
    }

    public function testKeepsDuplicatePacksAndTheirDerivedBoxes(): void
    {
        $names = $this->namesOf($this->parser->parse($this->fixture()));

        $this->assertSame(2, count(array_keys($names, 'Play Booster - Final Fantasy')));
        $this->assertSame(2, count(array_keys($names, 'Play Booster Box - Final Fantasy')));
    }

    #[TestWith(['skippedName' => 'Item With Empty Ticket Price'], 'Empty data-ticket-price is skipped')]
    #[TestWith(['skippedName' => 'Item With Non Numeric Ticket Price'], 'Non numeric data-ticket-price is skipped')]
    #[TestWith(['skippedName' => 'Item Without Ticket Price Attribute'], 'Missing data-ticket-price attribute is skipped')]
    #[TestWith(['skippedName' => 'Not A Prize Wall Item'], 'Element without the prize wall item class is ignored')]
    public function testSkipsMalformedItems(string $skippedName): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertNotContains($skippedName, $this->namesOf($items));
    }

    public function testSkipsItemWithoutName(): void
    {
        $items = $this->parser->parse($this->fixture());

        $tixPrices = array_map(static fn (PrizeWallItem $item): int => $item->tixPrice, $items);

        $this->assertNotContains(500, $tixPrices);
    }

    public function testReturnsSequentiallyIndexedList(): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertSame(range(0, 10), array_keys($items));
    }

    #[TestWith(['html' => '<html><body><p>No prize wall here</p></body></html>'], 'Page without prize wall items')]
    #[TestWith(['html' => ''], 'Empty string')]
    #[TestWith(['html' => '<div class="prize-wall-grid"></div>'], 'Empty prize wall grid')]
    public function testReturnsEmptyArrayWhenThereAreNoItems(string $html): void
    {
        $this->assertSame([], $this->parser->parse($html));
    }

    public function testLeavesEurPriceUnset(): void
    {
        $items = $this->parser->parse($this->fixture());

        $this->assertNull($items[0]->eurPrice);
        $this->assertNull($items[1]->eurPrice);
    }
}
