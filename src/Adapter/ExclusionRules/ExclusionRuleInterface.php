<?php

declare(strict_types=1);

namespace App\Adapter\ExclusionRules;

interface ExclusionRuleInterface
{
    /**
     * @param string $value the value this rule inspects, such as an item name or one of its tags
     * @return bool true when the item should be skipped, false when it should be kept
     */
    public function shouldSkipItem(string $value): bool;
}
