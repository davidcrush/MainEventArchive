# Data Import

Initial catalog seed and import pipeline design for Main Event Archive.

## Two paths

| Path | When | How |
|------|------|-----|
| **Initial seed** | v1 launch catalog | Artisan import from Wikidata/Wikipedia API |
| **Ongoing** | After launch | Staff admin UI (Filament) |

Staff always adds manually: spoiler flags, Cagematch URLs, verification. YouTube and timestamps deferred to v1.6.

## v1 import filter

```
promotion = WCW
show_type = PPV
date >= 1993-01-01
```

## Source evaluation

| Source | Verdict | Notes |
|--------|---------|-------|
| **Wikidata** | Primary | SPARQL + Wikidata API; CC0 structured data; event date, venue, promotion, participants |
| **Wikipedia** | Enrichment | MediaWiki Action API; CC BY-SA 4.0; match cards where Results tables parse reliably |
| **Wikipedia dumps** | Defer | Overkill for v1 scope |
| **Cagematch / ProFightDB** | Do not use for seed | No public API; scraping ratings/card data prohibited. Staff may run `shows:link-cagematch` to discover outbound URLs only — see [third-party-references.md](third-party-references.md) |
| **Staff CSV/JSON** | Fallback | Manual export from licensed sources |
| **Promotion APIs** | Unlikely | No historical back catalog APIs |

## Wikidata approach

1. Query wrestling events linked to WCW with date ≥ 1993
2. Map to `Show` + `Match` + `MatchParticipant`
3. Store QID in `source_id`, build `source_url`
4. Land in `pending_review` status

Example SPARQL direction (implementation will refine):

```sparql
# Find WCW PPV-style events from 1993+
# Filter by instance of wrestling event, promoted by WCW, date filter
```

Rate limits: respect Wikidata API etiquette; batch requests; cache responses during dev import runs.

## Wikipedia approach

1. Resolve Wikipedia page from Wikidata `sitelinks` or known PPV titles
2. Fetch wikitext/HTML via MediaWiki API (`action=parse` or `action=query`)
3. Parse `{{Infobox wrestling event}}` for venue, city, and attendance when present (see [wikipedia-parser.md](wikipedia-parser.md))
4. When the show has a **single** venue wikilink, fetch the venue Wikipedia page and upsert a `venues` row; set `shows.venue_id` (see [wikipedia-venue-parser.md](wikipedia-venue-parser.md))
5. Parse Results tables — expect promotion-specific parsers per template family; see [wikipedia-parser.md](wikipedia-parser.md) for supported formats and edge cases
6. Merge into staging show; mark warnings for unparseable rows

**Do not** scrape HTML outside the API.

## Licensing & attribution

| Source | License | Action |
|--------|---------|--------|
| Wikidata | CC0 (most items) | Link to entity; `/attribution` page |
| Wikipedia | CC BY-SA 4.0 | Attribute contributors; per-show source link where adapted |

Our value-add: curation, spoiler presentation, MEA ratings, watchlists, YouTube linking (v1.6+).

## Architecture

```php
interface ShowDataImporter
{
    public function import(ImportRequest $request): ImportResult;
}

// Implementations: WikidataShowImporter, WikipediaShowImporter
// Bound in AppServiceProvider or dedicated provider
// CLI: vendor/bin/sail artisan shows:import {source} {identifier}
// Venue backfill: vendor/bin/sail artisan shows:import-venues --from= --to=
```

`ImportResult` contains staged shows, matches, warnings, and source metadata.

## Review queue workflow

1. Import creates/updates shows with `status = pending_review`
2. Staff reviews card accuracy in Filament
3. Staff sets spoiler flags, external URLs
4. Staff publishes → `status = published`, `verified_at`, `verified_by`

## Re-sync policy

**Default:** One-time seed per show. Manual admin corrections afterward. Optional re-import command later with `--force` and diff review — not v1.

## Open implementation questions

- Wikipedia parser depth per PPV template — track edge cases in [wikipedia-parser.md](wikipedia-parser.md)
- Wikidata property mapping document (maintain mapping table in code/config)

## Related docs

- [ADR 002](../architecture/decisions/002-initial-seed-via-wikidata.md)
- [Admin workflow](admin-workflow.md)
- [Data model](data-model.md)
- [Third-party references](third-party-references.md)
