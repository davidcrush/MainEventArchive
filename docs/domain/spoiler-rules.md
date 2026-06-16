# Spoiler Rules

How Main Event Archive defines, stores, and exposes spoiler-sensitive data.

## Philosophy

Spoilers are **off by default**. Users opt in **per show** to see results, match lengths, and timestamps. Enforcement is **server-side** — never rely on hiding data only in the React layer.

## Tiers

| Tier | Content | Requirement |
|------|---------|-------------|
| **Hard** | Match results (winner, finish, title changes) | Never in API/UI when spoilers off |
| **Hard** | Match lengths, durations, timestamp ranges | Never when spoilers off |
| **Soft** | Surprise matches | Omit entire match from card when `is_surprise` |
| **Soft** | Surprise entrants | Show `placeholder_label` instead of name when flagged |
| **Soft** | Tournament/bracket advancement | Mask participants when `tournament_round >= 2` |

**Expectation:** Soft spoilers depend on staff flagging. UI may note: "We hide known surprises; some may slip through."

## Hard-spoiler field registry

These fields must be stripped from API responses and omitted from Inertia props when spoilers are off for the current show context:

```
winner_side, finish, duration_seconds,
timestamp_start, timestamp_end, title_changed
```

Also omit UI affordances: "Jump to match", duration display, result text.

### Participant ordering must not leak the result

`winner_side` is hidden when spoilers are off, but the **order** participants are displayed in can still leak the winner. The public card orders participants by `side` ascending, so if the winner were always stored as side 1 (as Wikipedia lists them), the winner would always appear first.

To prevent this, side order is made spoiler-safe **at import** (see [wikipedia-parser.md → Spoiler-safe side order](wikipedia-parser.md)):

- **Championship matches:** the champion (detected via `(c)`) is listed first. Safe because the champion may have won or lost.
- **Other decisive matches:** a deterministic, winner-independent shuffle (seeded by card position + sorted roster) — stable across re-imports, uncorrelated with the winner.

`winner_side` records the real winner independently and remains a hard-spoiler field. Manually curated matches in Filament are the curator's responsibility to order safely.

## Soft-spoiler behavior

### `Match.is_surprise = true`

- Entire match omitted from card when spoilers off
- Do not show "TBA" slots that confirm something extra happened

### `MatchParticipant.is_surprise_entrant = true`

- When spoilers off: show `placeholder_label` only (e.g. "Mystery opponent")
- When spoilers on: show real `name`

### `Match.tournament_round`

- `null` — not part of a tournament bracket mask; show participants normally
- `1` — opening bracket round (quarterfinals, round 1); show participants when spoilers off
- `2+` — advancement rounds (semifinals, final); when spoilers off, return `participant_line` only with `???` placeholders preserving side/team structure (e.g. `??? vs ???`, `??? & ??? vs ??? & ???`); omit `participants` array
- Match type and `title_name` remain visible (e.g. "Semifinal")
- Full bracket graph modeling deferred; staff sets round per match in Filament

## SpoilerContext

Central service determines whether spoilers are enabled:

```php
// Inputs (priority order TBD in implementation):
// 1. Per-show session flag (user clicked "Show spoilers" on this show)
// 2. Authenticated user global default (users.spoilers_enabled_default)
// 3. Guest session default (off)
// Optional: ?spoilers=1 query param for shareable links
```

Apply in:

- Eloquent API resources / Inertia shared data builders
- SEO meta tags (card-only descriptions when spoilers off)

## UX rules

| Spoilers | Show |
|----------|------|
| Off | Participants, match types, titles (championship name ok) |
| Off | Battle royals: `participant_line` only — featured entrants, not winner vs runner-up |
| Off | Tournament matches with `tournament_round >= 2`: `participant_line` with `???` placeholders only |
| Off | No winners, finishes, durations, timestamps |
| On | Full results + timestamps (when video exists, v1.6+) |

## Ratings vs spoilers

MEA aggregate ratings (average + count) are **visible when spoilers off** — community scores are not treated as spoilers.

Users may rate matches without enabling spoilers for that show (they may have seen the match elsewhere).

## Admin checklist (soft spoilers)

Staff should flag when applicable:

- Royal Rumble / Battle Royal surprise entrants
- Money in the Bank winner not on announced card
- Tournament final/advancement not advertised pre-show
- Debut or return not advertised
- Added match not on pre-show card

See [admin-workflow.md](admin-workflow.md).

## Testing requirements

Feature tests must assert:

- Spoilers off: hard fields absent from JSON/props
- Spoilers on: hard fields present
- Soft: surprise match absent when off; present when on
- Soft: tournament round 2+ shows `???` placeholders when off; real names when on

## Related docs

- [Data model](data-model.md)
- [Frontend conventions](../frontend/conventions.md)
- [Decisions](../product/decisions.md)
