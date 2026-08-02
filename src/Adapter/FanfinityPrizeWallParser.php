<?php

declare(strict_types=1);

namespace App\Adapter;

use App\Adapter\ExclusionRules\ByNameExclusion;
use App\Adapter\ExclusionRules\ByTagExclusion;
use App\Adapter\ExclusionRules\ExclusionRuleInterface;
use App\Dto\PrizeWallItem;
use DOMDocument;
use DOMElement;
use DOMXPath;

final class FanfinityPrizeWallParser
{
    private const array PACKS_PER_BOX = [
        'Play'      => 30,
        'Collector' => 12,
    ];

    private const string ITEM_CLASS = 'prize-wall-item-element';
    private const string NAME_CLASS = 'event-description-name';
    private const string BADGE_CLASS = 'badge';
    private const string TIX_PRICE_ATTRIBUTE = 'data-ticket-price';

    public function __construct(
        private readonly ExclusionRuleInterface $nameExclusion = new ByNameExclusion(),
        private readonly ExclusionRuleInterface $tagExclusion = new ByTagExclusion(),
    ) {
    }

    /**
     * @return PrizeWallItem[]
     */
    public function parse(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $xpath = new DOMXPath($this->loadDocument($html));
        $items = [];

        foreach ($xpath->query($this->classQuery(self::ITEM_CLASS)) as $element) {
            foreach ($this->extractItems(element: $element, xpath: $xpath) as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function loadDocument(string $html): DOMDocument
    {
        $document = new DOMDocument();

        // Without the encoding hint libxml falls back to ISO-8859-1 and mangles names such as "Album’n’Case".
        $previousErrorHandling = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);

        return $document;
    }

    /**
     * @return PrizeWallItem[] the pack itself, followed by its derived booster box where one applies
     */
    private function extractItems(DOMElement $element, DOMXPath $xpath): array
    {
        $tixPrice = trim($element->getAttribute(self::TIX_PRICE_ATTRIBUTE));

        if (!ctype_digit($tixPrice)) {
            return [];
        }

        $nameNode = $xpath->query($this->classQuery(self::NAME_CLASS), $element)->item(0);

        if ($nameNode === null) {
            return [];
        }

        $name = $this->collapseWhitespace($nameNode->textContent);

        if ($name === '') {
            return [];
        }

        if ($this->nameExclusion->shouldSkipItem($name)) {
            return [];
        }

        if ($this->hasExcludedTag($this->tagsOf(element: $element, xpath: $xpath))) {
            return [];
        }

        $pack = new PrizeWallItem(name: $name, tixPrice: (int) $tixPrice);
        $box  = $this->deriveBoosterBox(name: $pack->name, tixPrice: $pack->tixPrice);

        return $box === null ? [$pack] : [$pack, $box];
    }

    /**
     * The wall only ever lists single packs, so box entries have to be derived from them.
     */
    private function deriveBoosterBox(string $name, int $tixPrice): ?PrizeWallItem
    {
        $boosterTypes = implode('|', array_keys(self::PACKS_PER_BOX));

        if (preg_match(sprintf('/^(%s) Booster - (.+)$/', $boosterTypes), $name, $matches) !== 1) {
            return null;
        }

        [, $boosterType, $set] = $matches;

        return new PrizeWallItem(
            name:     sprintf('%s Booster Box - %s', $boosterType, $set),
            tixPrice: $tixPrice * self::PACKS_PER_BOX[$boosterType],
        );
    }

    /**
     * @return string[]
     */
    private function tagsOf(DOMElement $element, DOMXPath $xpath): array
    {
        $tags = [];

        foreach ($xpath->query($this->classQuery(self::BADGE_CLASS), $element) as $badge) {
            $tags[] = $this->collapseWhitespace($badge->textContent);
        }

        return $tags;
    }

    /**
     * @param string[] $tags
     */
    private function hasExcludedTag(array $tags): bool
    {
        foreach ($tags as $tag) {
            if ($this->tagExclusion->shouldSkipItem($tag)) {
                return true;
            }
        }

        return false;
    }

    private function classQuery(string $class): string
    {
        return sprintf('.//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $class);
    }

    private function collapseWhitespace(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
