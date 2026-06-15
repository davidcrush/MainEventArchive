<?php

namespace App\Services;

use App\Models\Show;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ShowSlugGenerator
{
    private const MONTH_ABBREVIATIONS = [
        1 => 'jan',
        2 => 'feb',
        3 => 'mar',
        4 => 'apr',
        5 => 'may',
        6 => 'jun',
        7 => 'jul',
        8 => 'aug',
        9 => 'sep',
        10 => 'oct',
        11 => 'nov',
        12 => 'dec',
    ];

    public function generate(string $title, CarbonInterface $date, ?int $excludeShowId = null): string
    {
        $year = $date->format('Y');
        $titleForSlug = preg_replace('/\s+\d{4}$/', '', $title) ?? $title;
        $baseSlug = Str::slug($titleForSlug).'-'.$year;

        if (! $this->needsDisambiguation($title, $date, $baseSlug, $excludeShowId)) {
            return $baseSlug;
        }

        $month = self::MONTH_ABBREVIATIONS[(int) $date->format('n')];
        $monthSlug = $baseSlug.'-'.$month;

        if (! $this->slugExists($monthSlug, $excludeShowId)) {
            return $monthSlug;
        }

        $daySlug = $monthSlug.'-'.$date->format('j');

        if (! $this->slugExists($daySlug, $excludeShowId)) {
            return $daySlug;
        }

        throw new \RuntimeException("Unable to generate unique slug for show [{$title}] on {$date->toDateString()}.");
    }

    private function needsDisambiguation(string $title, CarbonInterface $date, string $baseSlug, ?int $excludeShowId): bool
    {
        if ($this->slugExists($baseSlug, $excludeShowId)) {
            return true;
        }

        return $this->hasSameTitleAndYear($title, $date, $excludeShowId);
    }

    private function hasSameTitleAndYear(string $title, CarbonInterface $date, ?int $excludeShowId): bool
    {
        $query = Show::query()
            ->whereYear('date', $date->format('Y'))
            ->where('title', $title);

        if ($excludeShowId !== null) {
            $query->where('id', '!=', $excludeShowId);
        }

        return $query->exists();
    }

    private function slugExists(string $slug, ?int $excludeShowId): bool
    {
        $query = Show::query()->where('slug', $slug);

        if ($excludeShowId !== null) {
            $query->where('id', '!=', $excludeShowId);
        }

        return $query->exists();
    }
}
