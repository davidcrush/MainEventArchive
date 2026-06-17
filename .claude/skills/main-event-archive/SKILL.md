---
name: main-event-archive
description: "Apply this skill for any Main Event Archive (MEA) domain work: shows, matches, promotions, videos, spoilers, ratings, watchlists, data import, YouTube providers, or WCW/WWE catalog features. Use when implementing or documenting wrestling catalog, spoiler-safe APIs, Wikidata import, admin curation, or public browse/search UX. Always read docs/ first; ask when requirements are unclear."
license: MIT
metadata:
  author: main-event-archive
---

# Main Event Archive Domain Skill

Pro wrestling catalog application. **Catalog-first:** v1–v1.5 data only; YouTube at v1.6.

## Start here

| Doc | Purpose |
|-----|---------|
| [docs/README.md](../../../docs/README.md) | Documentation index |
| [docs/product/vision.md](../../../docs/product/vision.md) | Product principles |
| [docs/product/mvp-scope.md](../../../docs/product/mvp-scope.md) | v1 scope & roadmap |
| [docs/product/decisions.md](../../../docs/product/decisions.md) | Defaults & open items |
| [docs/product/video-linking-policy.md](../../../docs/product/video-linking-policy.md) | Third-party video rights & takedown (planned) |
| [docs/domain/data-model.md](../../../docs/domain/data-model.md) | Schema source of truth |
| [docs/domain/spoiler-rules.md](../../../docs/domain/spoiler-rules.md) | Hard vs soft spoilers |
| [docs/architecture/overview.md](../../../docs/architecture/overview.md) | Adapters & layout |

Also activate `laravel-best-practices` for Laravel PHP code.

## Product one-liner

Spoiler-safe pro wrestling show archive — index cards and link to video (YouTube first); we never host video.

## Non-negotiables

1. **Sail only** — `vendor/bin/sail` for PHP, Artisan, Composer, npm
2. **Spoilers server-side** — never leak hard-spoiler fields when off ([spoiler-rules.md](../../../docs/domain/spoiler-rules.md))
3. **No scraping** Cagematch/ProFightDB — Wikidata/Wikipedia API for seed ([data-import.md](../../../docs/domain/data-import.md))
4. **Cagematch link-out only** — no display/cache of their ratings ([third-party-references.md](../../../docs/domain/third-party-references.md))
5. **Video schema** — `videos.show_id` OR `videos.match_id`; full-show v1.6, per-match v1.7+
6. **Catalog before video** — don't ship YouTube features before v1.6 milestone
7. **Venues (single-arena only for now)** — `shows.venue_id` is set only when a show infobox has one venue wikilink. Multi-venue events remain text on the show until WWE v1.2+ ([wikipedia-venue-parser.md](../../../docs/domain/wikipedia-venue-parser.md))

## Known limitations

- **Multi-venue PPVs** — comma-joined `shows.venue` / `shows.city` with no `venue_id`. Acceptable for WCW v1. Before WWE catalog ingest, design `show_venues` (or equivalent). See [wikipedia-parser.md](../../../docs/domain/wikipedia-parser.md) (WrestleMania 2) and [wikipedia-venue-parser.md](../../../docs/domain/wikipedia-venue-parser.md).

## v1 scope reminder

- WCW PPVs 1993+
- Browse, search, spoilers, MEA ratings, watchlist, watched
- Filament admin; Inertia + React + Chakra public (when installed)
- No video playback in v1

## Workflow for agents

### Ask don't assume

Stop and ask the user when:

- Requirements conflict with docs
- Open item in [decisions.md](../../../docs/product/decisions.md) affects your task
- Schema change impacts future WWE/TV/brands/video milestones

### Planning format

When proposing approaches, provide:

1. Options with pros/cons
2. Recommendation
3. Risks or gaps the user may not have considered

Pushback on unclear specs is welcome.

### Before code changes

1. Read relevant `docs/` pages
2. `search-docs` for Laravel/Inertia/Chakra syntax
3. Check [data-model.md](../../../docs/domain/data-model.md) for field names
4. Write tests for spoiler leakage and auth gates

## Key contracts (implement behind interfaces)

- `VideoProvider` — [video-providers.md](../../../docs/architecture/video-providers.md)
- `ShowDataImporter` — [data-import.md](../../../docs/domain/data-import.md)
- `ShowEnricher` — [ai-enrichment.md](../../../docs/domain/ai-enrichment.md) (v1.6+)
- `SpoilerContext` — [spoiler-rules.md](../../../docs/domain/spoiler-rules.md)

## Design assets

Mocks and source logo: [docs/design/](../../../docs/design/README.md)

Production logo: `resources/images/brand/`

## Related skills

- `laravel-best-practices` — all Laravel PHP
