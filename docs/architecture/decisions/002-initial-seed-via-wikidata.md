# ADR 002: Initial Seed via Wikidata

## Status

Accepted

## Context

v1 needs WCW PPV catalog data without scraping proprietary wrestling databases. We require legitimate licensing and provenance tracking.

## Decision

- Primary seed: **Wikidata** via SPARQL/API (CC0 structured data)
- Optional enrichment: **Wikipedia** MediaWiki API for match cards (CC BY-SA)
- Import via `ShowDataImporter` contract; CLI `shows:import`
- All imports land in `pending_review` until staff publishes
- **Do not** scrape Cagematch or ProFightDB

## v1 filter

WCW PPVs, date ≥ 1993-01-01

## Consequences

- Build Wikidata property mapping and Wikipedia table parsers incrementally
- `/attribution` page required for Wikipedia-derived content
- Card coverage may be incomplete — staff fills gaps in admin

## Related

- [data-import.md](../../domain/data-import.md)
