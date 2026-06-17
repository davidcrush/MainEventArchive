<?php

namespace App\Services\Fandom;

use App\Data\ResolvedWikipediaPage;
use App\Models\Show;
use RuntimeException;

/**
 * Resolves a Nitro show to its prowrestling.fandom.com episode page. Titles are
 * deterministic by air date ("{F j, Y} Monday Nitro results"); an opensearch
 * lookup covers odd titles.
 */
class FandomNitroPageResolver
{
    public function __construct(
        private readonly FandomClient $client,
    ) {}

    public function resolve(Show $show): ResolvedWikipediaPage
    {
        if ($show->date === null) {
            throw new RuntimeException("Nitro show [{$show->title}] ({$show->slug}) has no date to resolve a Fandom page.");
        }

        $formattedDate = $show->date->format('F j, Y');
        $primaryTitle = "{$formattedDate} Monday Nitro results";
        $attempts = [];

        try {
            return $this->client->resolvePage($primaryTitle);
        } catch (RuntimeException $exception) {
            $attempts[] = "{$primaryTitle}: {$exception->getMessage()}";
        }

        foreach ($this->client->searchPageTitles("{$formattedDate} Monday Nitro") as $searchTitle) {
            if ($searchTitle === $primaryTitle) {
                continue;
            }

            try {
                return $this->client->resolvePage($searchTitle);
            } catch (RuntimeException $exception) {
                $attempts[] = "{$searchTitle}: {$exception->getMessage()}";
            }
        }

        throw new RuntimeException(
            "Could not resolve Fandom Nitro page for [{$show->title}] ({$show->slug}). Tried: ".implode('; ', $attempts).'.',
        );
    }
}
