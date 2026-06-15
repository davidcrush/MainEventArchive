<?php

namespace App\Importers;

use App\Models\Venue;
use App\Models\VenueAlias;
use App\Services\VenueSlugGenerator;
use App\Services\Wikipedia\WikipediaClient;
use App\Services\Wikipedia\WikipediaVenueInfoboxParser;

class WikipediaVenueImporter
{
    public const ALIAS_SOURCE_INFOBOX = 'wikipedia_infobox';

    public const ALIAS_SOURCE_REDIRECT = 'wikipedia_redirect';

    public const ALIAS_SOURCE_SHOW_INFOBOX = 'show_infobox';

    public function __construct(
        private readonly WikipediaClient $client,
        private readonly WikipediaVenueInfoboxParser $infoboxParser,
        private readonly VenueSlugGenerator $slugGenerator,
    ) {}

    public function importFromPageTitle(
        string $pageTitle,
        ?string $aliasFromShowInfobox = null,
        bool $refresh = false,
    ): Venue {
        $resolved = $this->client->resolvePage($pageTitle);
        $metadata = $this->infoboxParser->parse($resolved->wikitext, $resolved->canonicalTitle);

        $venue = Venue::query()->where('wikipedia_page_title', $resolved->canonicalTitle)->first();

        if ($venue === null) {
            $venue = Venue::query()->create([
                'name' => $metadata->name,
                'slug' => $this->slugGenerator->generate($metadata->name),
                'city' => $metadata->city,
                'state_province' => $metadata->stateProvince,
                'country' => $metadata->country,
                'capacity' => $metadata->capacity,
                'wikipedia_page_title' => $resolved->canonicalTitle,
                'wikipedia_url' => $this->buildWikipediaUrl($resolved->canonicalTitle),
                'imported_at' => now(),
            ]);
        } elseif ($refresh) {
            $venue->update([
                'name' => $metadata->name,
                'city' => $metadata->city,
                'state_province' => $metadata->stateProvince,
                'country' => $metadata->country,
                'capacity' => $metadata->capacity,
                'wikipedia_url' => $this->buildWikipediaUrl($resolved->canonicalTitle),
                'imported_at' => now(),
            ]);
        }

        $this->upsertAlias($venue, $resolved->redirectFrom, self::ALIAS_SOURCE_REDIRECT);
        $this->upsertAlias($venue, $aliasFromShowInfobox, self::ALIAS_SOURCE_SHOW_INFOBOX);

        foreach ($metadata->formerNames as $formerName) {
            $this->upsertAlias($venue, $formerName, self::ALIAS_SOURCE_INFOBOX);
        }

        return $venue->fresh(['aliases']);
    }

    private function upsertAlias(Venue $venue, ?string $name, string $source): void
    {
        if ($name === null || trim($name) === '') {
            return;
        }

        $name = trim($name);

        if (strcasecmp($name, $venue->name) === 0) {
            return;
        }

        VenueAlias::query()->firstOrCreate(
            [
                'venue_id' => $venue->id,
                'name' => $name,
            ],
            [
                'source' => $source,
            ],
        );
    }

    private function buildWikipediaUrl(string $pageTitle): string
    {
        return 'https://en.wikipedia.org/wiki/'.str_replace(' ', '_', $pageTitle);
    }
}
