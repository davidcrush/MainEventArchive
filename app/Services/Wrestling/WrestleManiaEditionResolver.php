<?php

namespace App\Services\Wrestling;

class WrestleManiaEditionResolver
{
    /**
     * @return array{edition: int}|null
     */
    public function parseStreamingTitle(string $title): ?array
    {
        $title = html_entity_decode(trim($title), ENT_QUOTES | ENT_HTML5);
        $title = preg_replace('/^(?:WWE|WWF)\s+/i', '', $title) ?? $title;
        $title = preg_replace('/\s+(?:Saturday|Sunday|Part \d+|Night \d+).*$/i', '', $title) ?? $title;
        $title = trim($title);

        if ($title === '' || ! preg_match('/^WrestleMania\b/i', $title)) {
            return null;
        }

        if (preg_match('/^WrestleMania 2000$/i', $title) === 1) {
            return ['edition' => 16];
        }

        if (preg_match('/^WrestleMania (\d{1,2})\b/i', $title, $matches) === 1) {
            return ['edition' => (int) $matches[1]];
        }

        return null;
    }

    public function extractCatalogEdition(string $catalogTitle): ?int
    {
        $title = html_entity_decode(trim($catalogTitle), ENT_QUOTES | ENT_HTML5);

        if (preg_match('/^WrestleMania 2000$/i', $title) === 1) {
            return 16;
        }

        if (preg_match('/^WrestleMania X-Seven\b/i', $title) === 1) {
            return 17;
        }

        if (preg_match('/^WrestleMania X8\b/i', $title) === 1) {
            return 18;
        }

        if (preg_match('/^WrestleMania XIX\b/i', $title) === 1) {
            return 19;
        }

        if (preg_match('/^WrestleMania XX\b/i', $title) === 1) {
            return 20;
        }

        if (preg_match('/^WrestleMania ([IVXLCDM]+)\b/i', $title, $matches) === 1) {
            return $this->romanToArabic($matches[1]);
        }

        if (preg_match('/^WrestleMania (\d{1,2})\b/i', $title, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    public function matchesEdition(int $edition, string $catalogTitle): bool
    {
        $catalogEdition = $this->extractCatalogEdition($catalogTitle);

        return $catalogEdition !== null && $catalogEdition === $edition;
    }

    private function romanToArabic(string $roman): ?int
    {
        $roman = strtoupper($roman);

        $numerals = [
            'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5,
            'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10,
            'XI' => 11, 'XII' => 12, 'XIII' => 13, 'XIV' => 14, 'XV' => 15,
            'XVI' => 16, 'XVII' => 17, 'XVIII' => 18, 'XIX' => 19, 'XX' => 20,
            'XXI' => 21, 'XXII' => 22, 'XXIII' => 23, 'XXIV' => 24, 'XXV' => 25,
            'XXVI' => 26, 'XXVII' => 27, 'XXVIII' => 28, 'XXIX' => 29, 'XXX' => 30,
            'XXXI' => 31, 'XXXII' => 32, 'XXXIII' => 33, 'XXXIV' => 34, 'XXXV' => 35,
            'XXXVI' => 36, 'XXXVII' => 37, 'XXXVIII' => 38, 'XXXIX' => 39, 'XL' => 40,
        ];

        return $numerals[$roman] ?? null;
    }
}
