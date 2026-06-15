# Wikipedia Venue Parser

Reference for venue records populated from Wikipedia during show import. Update when new infobox edge cases appear.

**Code:** `App\Services\Wikipedia\WikipediaVenueInfoboxParser`, `App\Importers\WikipediaVenueImporter`

## When venues are created

During `shows:import wikipedia`, if the show infobox `venue` field parses to **exactly one** wikilink (e.g. `[[Ocean Center]]`), MEA:

1. Resolves the venue Wikipedia page (follows redirects)
2. Parses the venue infobox for location, capacity, former names
3. Creates or updates a `venues` row keyed by canonical `wikipedia_page_title`
4. Sets `shows.venue_id`

Venue import failures **warn** but do not fail the show import.

Backfill existing shows:

```bash
vendor/bin/sail artisan shows:import-venues --from=1993 --to=2001
vendor/bin/sail artisan shows:import-venues --slug=starrcade-1996
vendor/bin/sail artisan shows:import-venues --force   # re-fetch venue metadata
```

## Multi-venue limitation (important)

Some events use **multiple arenas** on one card (e.g. [WrestleMania 2](https://en.wikipedia.org/wiki/WrestleMania_2)). Wikipedia lists bullet-separated venues:

```
| venue =
*[[Nassau Veterans Memorial Coliseum]]
*[[Allstate Arena|Rosemont Horizon]]
*[[Los Angeles Memorial Sports Arena]]
```

**Policy:** when the show infobox parses to **more than one** venue wikilink:

- Keep comma-joined text on `shows.venue` / `shows.city` (unchanged)
- Do **not** set `shows.venue_id`
- Do **not** create venue rows from that show

This is acceptable for **WCW v1** (single-arena PPVs). **WWE catalog (v1.2+)** will require a follow-up design (`show_venues` pivot, per-venue attendance, etc.). Do not link multi-venue shows to a single venue without an explicit design change.

See also the multi-venue show metadata section in [wikipedia-parser.md](wikipedia-parser.md).

## Supported venue infobox templates

Parser matches (case-insensitive):

- `{{Infobox venue}}`
- `{{Infobox stadium}}`
- `{{Infobox indoor arena}}`
- `{{Infobox convention center}}`
- `{{Infobox arena}}`
- `{{Infobox sports venue}}`

## Infobox → database mapping

| Infobox param | DB field | Notes |
|---------------|----------|-------|
| `name`, `stadium name`, `venue name` | `venues.name` | fallback: canonical Wikipedia title |
| `city`, `location city` | `venues.city` | |
| `state`, `state/province`, `province` | `venues.state_province` | |
| `country` | `venues.country` | |
| `location`, `address`, `location place` | city/state/country | comma-split heuristic when explicit fields absent |
| `capacity`, `seating capacity`, … | `venues.capacity` | first integer; current value only |
| `former names`, `former_names`, `previous names` | `venue_aliases` | source `wikipedia_infobox` |

Missing infobox → minimal venue row (name + Wikipedia URL; location/capacity null).

Location fields are normalized by `VenueLocationNormalizer` after parsing: strip address lines before `<br>` in city, strip zip codes from state/country, normalize US country labels to `US`, and infer `US` when a US state is present. Backfill existing rows with `vendor/bin/sail artisan venues:normalize-locations`.

## Historical names (aliases)

Stored in `venue_aliases`:

| Source | When |
|--------|------|
| `wikipedia_redirect` | Request used a redirect (e.g. `Rosemont Horizon` → `Allstate Arena`) |
| `show_infobox` | Piped wikilink display text differs from page title |
| `wikipedia_infobox` | `former names` parameter on venue page |

Aliases equal to the venue's current `name` are skipped.

## Show fields vs venue fields

- `shows.venue` / `shows.city` — event-level display from **wrestling event** infobox (unchanged)
- `venues.*` — structured metadata from the **venue** Wikipedia page
- `shows.venue_id` — FK when single-venue link succeeds

## Related

- [Data import](data-import.md)
- [Data model](data-model.md)
- [Wikipedia show parser](wikipedia-parser.md)
