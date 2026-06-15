<?php

namespace App\Services;

class CatalogTitleMatcher
{
    public function matches(string $showTitle, string $targetCatalogTitle): bool
    {
        if (strcasecmp($showTitle, $targetCatalogTitle) === 0) {
            return true;
        }

        if (strcasecmp($this->stripLeadingArticle($showTitle), $this->stripLeadingArticle($targetCatalogTitle)) === 0) {
            return true;
        }

        return $this->normalizeComparableKey($showTitle) === $this->normalizeComparableKey($targetCatalogTitle);
    }

    private function stripLeadingArticle(string $title): string
    {
        return trim(preg_replace('/^The\s+/i', '', trim($title)) ?? trim($title));
    }

    private function normalizeComparableKey(string $title): string
    {
        $title = html_entity_decode(trim($title), ENT_QUOTES | ENT_HTML5);
        $title = preg_replace('/^The\s+/i', '', $title) ?? $title;
        $title = strtolower($title);

        return preg_replace('/[^a-z0-9]/', '', $title) ?? '';
    }
}
