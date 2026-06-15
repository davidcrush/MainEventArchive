# UX Design Brief

Brief for AI or human designers creating Main Event Archive mockups.

## Product

Pro wrestling show index — spoiler-safe card browsing, community ratings, watchlist. Video linking comes in v1.6; v1 mocks use **video placeholder**, not a player.

## v1 content

WCW PPVs 1993+ (e.g. Starrcade 1997, Halloween Havoc 1996)

## Pages (priority)

1. **Show detail** — spoilers OFF and ON (two states) — highest priority
2. Browse / catalog
3. Home
4. Search results
5. Watchlist (auth)
6. Global header/footer

## Show page must include

- Title, date, venue, promotion, PPV badge
- Match card (participants only when spoilers off)
- Per-show "Show spoilers" toggle
- MEA star ratings (show + matches) — our scores only
- "View on Cagematch" link badge — **no Cagematch numeric score**
- Watchlist + watched actions
- Video placeholder ("Video linking coming soon")

## Spoilers ON adds

- Winners, finishes, durations
- Jump-to-match buttons (for future video)

## Tech

React + Chakra UI — design should map to Chakra components (Card, Badge, Switch, Button, Star rating)

## Accessibility

WCAG 2.1 AA, visible focus, semantic structure, screen reader labels on toggles

## Tone

Welcoming, fan-focused, clean archive — not cluttered wrestling poster aesthetic unless intentional

## Assets

Place mocks in `mocks/`, source logo in `brand/`. See [README.md](README.md).

## Full agent prompt

See planning conversation or extend this brief when engaging design agents. Key rules: spoilers default off, no Cagematch scores, no video player in v1.

## Related

- [Frontend conventions](../frontend/conventions.md)
- [Spoiler rules](../domain/spoiler-rules.md)
- [MVP scope](../product/mvp-scope.md)
