# Wikipedia Results Parser

Reference for Wikipedia `{{Pro Wrestling results table}}` formats and how MEA imports them. Update this doc when new edge cases appear during curation.

**Code:** `App\Services\Wikipedia\WikipediaResultsParser` (match cards), `App\Services\Wikipedia\WikipediaInfoboxParser` (show metadata)

## Show metadata (venue / city / attendance)

Wikipedia enrichment reads `{{Infobox wrestling event}}` (case-insensitive) from the same page wikitext used for match import.

**Code:** `App\Services\Wikipedia\WikipediaInfoboxParser`

| Infobox param | DB field | Rules |
|---------------|----------|-------|
| `venue` | `shows.venue` | Strip refs/links; comma-join bullet lists |
| `city` | `shows.city` | Same as venue; fallback to `location` if `city` absent |
| `attendance` | `shows.attendance` | First integer in value; strip commas; reject zero / unparseable text |

**Overwrite policy:** when Wikipedia parses a value, re-import **overwrites** existing venue, city, or attendance. Missing or unparseable fields leave the current DB value unchanged.

### Single-venue (typical WCW PPV)

```
|venue      = [[Ocean Center]]<ref name=pwh/>
|city       = [[Daytona Beach, Florida]]<ref name=pwh/>
|attendance = 8,300<ref name=pwh/>
```

Stored: `venue = Ocean Center`, `city = Daytona Beach, Florida`, `attendance = 8300`.

### Multi-venue edge case (comma-joined)

Some shows use one event at multiple arenas (e.g. [WrestleMania 2](https://en.wikipedia.org/wiki/WrestleMania_2)). Wikipedia lists bullet-separated venues and cities:

```
| venue =
*[[Nassau Veterans Memorial Coliseum]]
*[[Allstate Arena|Rosemont Horizon]]
*[[Los Angeles Memorial Sports Arena]]
| city =
*[[Uniondale, New York]]
*[[Rosemont, Illinois]]
*[[Los Angeles, California]]
| attendance = 40,085 (combined)
```

Stored:

- `venue`: `Nassau Veterans Memorial Coliseum, Rosemont Horizon, Los Angeles Memorial Sports Arena`
- `city`: `Uniondale, New York, Rosemont, Illinois, Los Angeles, California`
- `attendance`: `40085` (Wikipedia’s **combined** total — not per-venue breakdown)

Per-venue attendance is **out of scope** for v1; staff can note combined attendance in curation if needed. We do not split multi-venue into separate rows or a locations table. Structured venue linking (`shows.venue_id`) also skips multi-venue shows — see [wikipedia-venue-parser.md](wikipedia-venue-parser.md).

### Unparseable attendance

Values like `Sold out` or empty params → `attendance` left unchanged on re-import (`null` from parser).

## Supported formats

### Standard decisive match

```
[[Winner]] defeated [[Loser]]
[[Winner]] (with [[Manager]]) defeated [[Loser]]
[[Winner]] defeated [[Loser]] by [[Pin (professional wrestling)|pinfall]]
[[Team]] ([[Member1]] and [[Member2]]) defeated [[Other Team]] ([[Member3]] and [[Member4]])
```

| Field | Mapping |
|-------|---------|
| Winner | Side 1 |
| Loser | Side 2+ (one side per team in triangle matches) |
| Tag teams | `Team (Member1 & Member2)` — team name with members in parentheses |
| Comma / and lists | `[[A]], [[B]] and [[C]]` on one side → `A & B & C` (six-man tags, multi-person sides) |
| Finish | Text after `by`, default `pinfall` |
| Managers | `(with [[Name]])` stripped before participant extraction |

**Examples:** Most WCW PPV undercard matches; World War 3 1996 match 8 (triangle tag); Starrcade 1997 match 2 (six-man tag with comma-separated winners).

**Side grouping:** Loser segments split into separate sides only when the stipulation contains `triangle` (three-way matches). All other formats — including six-man tags and standard tag teams — combine each side into one participant row.

### Battle royal — winner + last eliminated

```
[[Winner]] won by last eliminating [[Runner-up]]
[[Winner]] won by "last eliminating" [[Runner-up]]
```

| Field | Mapping |
|-------|---------|
| Winner | Side 1 |
| Last eliminated | Side 2 (runner-up) |
| Finish | `last_elimination` |
| Match type | `battle_royal` when stipulation contains "battle royal" |

**Examples:**

- World War 3 1996 match 9 — `[[The Giant]] won by last eliminating [[Lex Luger]]`
- World War 3 1995 — `Randy Savage won by "last eliminating" [[Hulk Hogan]]`
- Survivor Series 2001 match 6 — winner + last eliminated stored as result participants; full entrant roster from `<ref>` footnotes stored in `entrant_names` for spoiler-safe card lines
- Royal Rumble 1996 — `[[Shawn Michaels]] won by last eliminating [[Diesel]]`

### No contest

```
[[Team A]] and [[Partner]] vs. [[Team B]], [[Partner 2]] and [[Partner 3]] ended in a [[no contest]]
```

| Field | Mapping |
|-------|---------|
| Participants | Side 1 / side 2 from `vs.` split |
| Winner | `null` |
| Finish | `no_contest` |

**Example:** Bash at the Beach 1996 main event (nWo formation).

### Page title with tagline

Some WCW pages use a subtitle instead of `(Year)`:

| Catalog title | Wikipedia page |
|---------------|----------------|
| Fall Brawl 1996 | Fall Brawl '96: War Games |

Importer tries standard `(Year)` title, event-specific variants, then Wikipedia search (`{title} WCW`).

### Non-PPV matches (dark / Main Event / Heat / Free For All)

Template `noteN` values:

| `noteN` | Meaning | `is_ppv` |
|---------|---------|----------|
| `dark` | Dark match (before broadcast or after the PPV, e.g. In Your House post-show) | `false` |
| `wcwme` | Aired on WCW Main Event before PPV | `false` |
| `heat` | Aired on WWE Sunday Night Heat before PPV | `false` |
| `ffa` | Aired on WWE Free For All before PPV (In Your House era) | `false` |
| (none) | PPV match | `true` |

Wikitable suffixes: `1D` (dark), `2ME` (Main Event), `1H` (Heat), `1F` (Free For All) → `is_ppv = false`.

**Public site:** only `is_ppv = true` matches appear on the show card. Admin shows all matches for verification.

## Known formats — not yet parsed

Document here when import fails or partial data is expected. Staff can complete in Filament.

| Format | Notes | Example source |
|--------|-------|----------------|
| **Full entrant list (footnote)** | Match row has winner + last eliminated; full roster in `<ref>…</ref>` on the match line | Imported to `matches.entrant_names`; result participants stay winner + last eliminated only |
| **Final four** | Winner plus three remaining wrestlers before final elimination | TBD — add when found |
| **Winner only** | `[[Winner]] won` with no runner-up | TBD — add when found |
| **Draw / no contest** | `vs.` without `defeated` | Parsed when line contains `ended in a no contest` |
| **Non-template wikitable** | Older pages use `{| class="wikitable" |}` rows | Fallback parser; limited coverage |

## Participant rules

1. **Always expect a winner** on side 1 when the row parses successfully.
2. **Runner-up** (last eliminated) on side 2 when present — most battle royals and all standard matches.
3. **Additional entrants** (full roster, final four) — not imported in v1; listed above for future parser work.
4. **Surprise entrants** — not inferred from Wikipedia text in v1; set `is_surprise_entrant` in admin.

## Stipulation hints

| Stipulation text | `match_type` |
|------------------|--------------|
| contains `battle royal`, `world war 3`, or `royal rumble` | `battle_royal` |
| `finish = last_elimination` | `battle_royal` |
| contains `tag team` | `tag` |
| contains `no disqualification` | `no_disqualification` |
| contains `singles` | `singles` |
| default | `singles` |

Title name extracted from `for the …` in stipulation when present.

## Import behavior

- One bad row currently fails the **entire show** import (transaction rolls back). Fix parser or enter that match manually, then re-import.
- Re-import **replaces** all matches on the show.
- Dark / Main Event / Heat / Free For All matches: `noteN = dark`, `noteN = wcwme`, `noteN = heat`, or `noteN = ffa` sets `is_ppv = false` (hidden on public card; visible in admin).

## Adding a new edge case

1. Run `vendor/bin/sail artisan shows:import wikipedia {slug}` and note the warning.
2. Fetch wikitext: `WikipediaClient::fetchWikitext('Page Title (Year)')`.
3. Add a row to **Known formats — not yet parsed** or **Supported formats** above.
4. Add a unit test fixture in `tests/Unit/WikipediaResultsParserTest.php`.
5. Implement parser branch in `WikipediaResultsParser`.

## Related

- [Data import](data-import.md)
- [Data model](data-model.md)
- [Admin workflow](admin-workflow.md)
