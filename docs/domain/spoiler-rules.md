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
| **Soft** | Tournament/bracket outcomes | Hide when flagged; full modeling deferred |

**Expectation:** Soft spoilers depend on staff flagging. UI may note: "We hide known surprises; some may slip through."

## Hard-spoiler field registry

These fields must be stripped from API responses and omitted from Inertia props when spoilers are off for the current show context:

```
winner_side, finish, duration_seconds,
timestamp_start, timestamp_end, title_changed
```

Also omit UI affordances: "Jump to match", duration display, result text.

## Soft-spoiler behavior

### `Match.is_surprise = true`

- Entire match omitted from card when spoilers off
- Do not show "TBA" slots that confirm something extra happened

### `MatchParticipant.is_surprise_entrant = true`

- When spoilers off: show `placeholder_label` only (e.g. "Mystery opponent")
- When spoilers on: show real `name`

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

## Related docs

- [Data model](data-model.md)
- [Frontend conventions](../frontend/conventions.md)
- [Decisions](../product/decisions.md)
