<?php

namespace App\Services\Wikipedia;

use App\Models\Show;
use Illuminate\Support\Str;

class WikipediaPageTitleResolver
{
    /**
     * @return list<string>
     */
    public function candidates(Show $show): array
    {
        $candidates = [];

        $override = $this->wikipediaPageTitleOverride($show->title);

        if ($override !== null) {
            $candidates[] = $override;
        }

        if ($this->appendInYourHouseCandidates($candidates, $show->title)) {
            return array_values(array_unique($candidates));
        }

        if ($this->appendWrestleManiaCandidates($candidates, $show->title)) {
            return array_values(array_unique($candidates));
        }

        if (preg_match('/^(.+?)\s+(\d{4})$/', $show->title, $matches) === 1) {
            $name = trim($matches[1]);
            $year = $matches[2];
            $shortYear = substr($year, 2);

            $yearCandidates = [
                "{$name} ({$year})",
                "The {$name} ({$year})",
                $show->title,
            ];

            foreach ($yearCandidates as $yearCandidate) {
                foreach ($this->articleNormalizedTitles($yearCandidate) as $normalizedTitle) {
                    $candidates[] = $normalizedTitle;
                }
            }

            if (strcasecmp($name, 'Fall Brawl') === 0) {
                $candidates[] = "Fall Brawl '{$shortYear}: War Games";
            }
        } else {
            foreach ($this->articleNormalizedTitles($show->title) as $normalizedTitle) {
                $candidates[] = $normalizedTitle;
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param  list<string>  $candidates
     */
    private function appendInYourHouseCandidates(array &$candidates, string $catalogTitle): bool
    {
        $parsed = $this->parseInYourHouseCatalogTitle($catalogTitle);

        if ($parsed === null) {
            return false;
        }

        ['number' => $number, 'subtitle' => $subtitle] = $parsed;
        $wikipediaSubtitle = $this->wikipediaSubtitleCase($subtitle);

        $candidates[] = "In Your House {$number}";
        $candidates[] = "In Your House {$number}: {$wikipediaSubtitle}";
        $candidates[] = "{$wikipediaSubtitle}: In Your House";

        return true;
    }

    /**
     * @return array{number: string, subtitle: string}|null
     */
    private function parseInYourHouseCatalogTitle(string $catalogTitle): ?array
    {
        if (preg_match('/^In Your House (\d+): (.+) (\d{4})(?:\s+\d{4})?$/', $catalogTitle, $matches) !== 1) {
            return null;
        }

        $subtitle = trim($matches[2]);
        $subtitle = preg_replace('/\s+\d{4}$/', '', $subtitle) ?? $subtitle;

        return [
            'number' => $matches[1],
            'subtitle' => $subtitle,
        ];
    }

    /**
     * @param  list<string>  $candidates
     */
    private function appendWrestleManiaCandidates(array &$candidates, string $catalogTitle): bool
    {
        if (preg_match('/^WrestleMania 2000$/i', $catalogTitle) === 1) {
            $candidates[] = 'WrestleMania 2000';

            return true;
        }

        if (preg_match('/^WrestleMania (.+) (\d{4})$/i', $catalogTitle, $matches) !== 1) {
            return false;
        }

        $edition = trim($matches[1]);

        foreach ($this->articleNormalizedTitles("WrestleMania {$edition}") as $normalizedTitle) {
            $candidates[] = $normalizedTitle;
        }

        return true;
    }

    private function wikipediaSubtitleCase(string $subtitle): string
    {
        $words = preg_split('/\s+/', trim($subtitle)) ?: [];
        $minorWords = ['of', 'the', 'in', 'at', 'and', 'vs', 'for', 'from', 'to', 'a', 'an'];
        $cased = [];

        foreach ($words as $index => $word) {
            $lower = strtolower($word);

            if ($index > 0 && in_array($lower, $minorWords, true)) {
                $cased[] = $lower;

                continue;
            }

            $cased[] = Str::title($lower);
        }

        return implode(' ', $cased);
    }

    /**
     * @return list<string>
     */
    private function articleNormalizedTitles(string $title): array
    {
        $titles = [$title];

        $normalized = preg_replace_callback(
            '/\b(Of|The|At|And|Vs|In)\b/',
            static fn (array $matches): string => strtolower($matches[1]),
            $title,
        );

        if (is_string($normalized) && $normalized !== $title) {
            $titles[] = $normalized;
        }

        return $titles;
    }

    public function resolve(Show $show, ?string $identifier = null): string
    {
        if ($identifier !== null && $identifier !== '') {
            return $this->normalizePageTitle($identifier);
        }

        return $this->candidates($show)[0];
    }

    private function normalizePageTitle(string $value): string
    {
        if (Str::contains($value, '_') && ! Str::contains($value, ' ')) {
            return str_replace('_', ' ', $value);
        }

        return $value;
    }

    private function wikipediaPageTitleOverride(string $title): ?string
    {
        $overrides = $this->wikipediaPageTitleOverrides();

        return $overrides[$title] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function wikipediaPageTitleOverrides(): array
    {
        static $overrides = null;

        if ($overrides !== null) {
            return $overrides;
        }

        $overrides = [];

        foreach ($this->catalogDataPaths() as $path) {
            if (! is_file($path)) {
                continue;
            }

            /** @var list<array{title: string, wikipedia_page_title?: string|null}> $events */
            $events = require $path;

            foreach ($events as $event) {
                if (filled($event['wikipedia_page_title'] ?? null)) {
                    $overrides[$event['title']] = $event['wikipedia_page_title'];
                }
            }
        }

        return $overrides;
    }

    /**
     * @return list<string>
     */
    private function catalogDataPaths(): array
    {
        return [
            database_path('seeders/data/wcw_pre1990_ppvs.php'),
            database_path('seeders/data/wcw_clash_catalog.php'),
            database_path('seeders/data/wwe_ppv_overrides.php'),
        ];
    }
}
