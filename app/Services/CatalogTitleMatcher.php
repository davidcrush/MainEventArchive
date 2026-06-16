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

    public function fuzzyPhraseMatches(string $left, string $right, float $minimumSimilarity = 85.0): bool
    {
        $left = $this->normalizeFuzzyPhrase($left);
        $right = $this->normalizeFuzzyPhrase($right);

        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        similar_text($left, $right, $percent);

        return $percent >= $minimumSimilarity;
    }

    public function extractInYourHouseCatalogParts(string $catalogTitle): ?array
    {
        if (preg_match('/^In Your House (\d+):\s*(.+?)\s+((?:19|20)\d{2})\b/i', $catalogTitle, $matches) !== 1) {
            return null;
        }

        return [
            'number' => (int) $matches[1],
            'subtitle' => trim($matches[2]),
        ];
    }

    private function stripLeadingArticle(string $title): string
    {
        return trim(preg_replace('/^The\s+/i', '', trim($title)) ?? trim($title));
    }

    private function normalizeComparableKey(string $title): string
    {
        return $this->normalizeFuzzyPhrase($title, stripArticles: true, stripNonAlphanumeric: true);
    }

    private function normalizeFuzzyPhrase(string $phrase, bool $stripArticles = false, bool $stripNonAlphanumeric = false): string
    {
        $phrase = html_entity_decode(trim($phrase), ENT_QUOTES | ENT_HTML5);

        if ($stripArticles) {
            $phrase = preg_replace('/^The\s+/i', '', $phrase) ?? $phrase;
        }

        $phrase = strtolower($phrase);

        if ($stripNonAlphanumeric) {
            return preg_replace('/[^a-z0-9]/', '', $phrase) ?? '';
        }

        $phrase = preg_replace('/[^\p{L}\p{N}\s]/u', '', $phrase) ?? $phrase;

        return trim(preg_replace('/\s+/', ' ', $phrase) ?? $phrase);
    }
}
