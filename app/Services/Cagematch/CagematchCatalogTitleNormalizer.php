<?php

namespace App\Services\Cagematch;

use Carbon\CarbonInterface;

class CagematchCatalogTitleNormalizer
{
    public function normalize(string $cagematchTitle, CarbonInterface $date): string
    {
        $title = html_entity_decode($cagematchTitle, ENT_QUOTES | ENT_HTML5);
        $title = preg_replace('/^(NWA|WCW\/nWo|WCW|AAA|nWo|WWF|WWE)\s+/i', '', $title) ?? $title;
        $title = preg_replace('/\s+-\s+.*$/', '', $title) ?? $title;
        $title = trim($title, " \t\n\r\"'");
        $title = $this->normalizeClashTitle($title);
        $year = (int) $date->format('Y');

        if (preg_match('/^The Great American Bash$/i', $title) === 1) {
            return "Great American Bash {$year}";
        }

        if (preg_match('/^Bash At The Beach$/i', $title) === 1) {
            return "Bash at the Beach {$year}";
        }

        if (preg_match('/^BattleBowl$/i', $title) === 1) {
            return "Battlebowl {$year}";
        }

        if (preg_match('/^SuperBrawl$/i', $title) === 1) {
            return "SuperBrawl {$year}";
        }

        if (preg_match('/^Hog Wild$/i', $title) === 1) {
            return "Hog Wild {$year}";
        }

        if (preg_match('/^New Blood Rising$/i', $title) === 1) {
            return "New Blood Rising {$year}";
        }

        if (preg_match('/^Sin$/i', $title) === 1) {
            return "Sin {$year}";
        }

        if (preg_match('/^Greed$/i', $title) === 1) {
            return "Greed {$year}";
        }

        if (preg_match('/^SuperBrawl Revenge$/i', $title) === 1) {
            return "SuperBrawl Revenge {$year}";
        }

        if (preg_match('/^Souled Out$/i', $title) === 1) {
            return "Souled Out {$year}";
        }

        if (preg_match('/^Millennium Final$/i', $title) === 1) {
            return "Millennium Final {$year}";
        }

        if (preg_match('/^When Worlds Collide$/i', $title) === 1) {
            return 'When Worlds Collide';
        }

        if (preg_match('/^SuperBrawl (II|III|IV|V|VI|VII|VIII|IX)$/i', $title, $matches) === 1) {
            return 'SuperBrawl '.strtoupper($matches[1]);
        }

        if (preg_match('/^SuperBrawl (\d{4})$/i', $title) === 1) {
            return "SuperBrawl {$year}";
        }

        if (preg_match('/^WrestleWar$/i', $title) === 1) {
            return "WrestleWar {$year}";
        }

        if (preg_match('/^Bunkhouse Stampede$/i', $title) === 1) {
            return "Bunkhouse Stampede {$year}";
        }

        if (preg_match('/^Starrcade$/i', $title) === 1) {
            return "Starrcade {$year}";
        }

        if (preg_match('/^Clash of the Champions [IVXLCDM]+$/i', $title) === 1) {
            return $title;
        }

        if (preg_match('/^Starrcade (\d{4})$/i', $title) === 1) {
            return "Starrcade {$year}";
        }

        if (preg_match('/^WrestleMania X-Seven$/i', $title) === 1) {
            return "WrestleMania X-Seven {$year}";
        }

        if (preg_match('/^In Your House (\d+): (.+)$/i', $title, $matches) === 1) {
            $subtitle = trim($matches[2]);
            $subtitle = preg_replace('/\s+\d{4}$/', '', $subtitle) ?? $subtitle;

            return "In Your House {$matches[1]}: {$subtitle} {$year}";
        }

        if (preg_match('/^(Halloween Havoc|Fall Brawl|Spring Stampede|Slamboree|Uncensored|Mayhem|World War 3|Beach Blast) (\d{4})$/i', $title, $matches) === 1) {
            return ucwords(strtolower($matches[1])).' '.$matches[2];
        }

        if (preg_match('/^Road Wild (\d{4})$/i', $title) === 1) {
            return "Road Wild {$year}";
        }

        if (preg_match('/(\d{4})$/', $title) === 1) {
            return $title;
        }

        return "{$title} {$year}";
    }

    private function normalizeClashTitle(string $title): string
    {
        if (preg_match('/^Clash of the Champions (\d+)\b/i', $title, $matches) === 1) {
            $roman = $this->clashArabicToRoman((int) $matches[1]);

            if ($roman !== null) {
                return "Clash of the Champions {$roman}";
            }
        }

        if (preg_match('/^Clash of the Champions ([IVXLCDM]+)$/i', $title, $matches) === 1) {
            return 'Clash of the Champions '.strtoupper($matches[1]);
        }

        return $title;
    }

    private function clashArabicToRoman(int $number): ?string
    {
        $numerals = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V',
            6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X',
            11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV', 15 => 'XV',
            16 => 'XVI', 17 => 'XVII', 18 => 'XVIII', 19 => 'XIX', 20 => 'XX',
            21 => 'XXI', 22 => 'XXII', 23 => 'XXIII', 24 => 'XXIV', 25 => 'XXV',
            26 => 'XXVI', 27 => 'XXVII', 28 => 'XXVIII', 29 => 'XXIX', 30 => 'XXX',
            31 => 'XXXI', 32 => 'XXXII', 33 => 'XXXIII', 34 => 'XXXIV', 35 => 'XXXV',
        ];

        return $numerals[$number] ?? null;
    }
}
