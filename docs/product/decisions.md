# Documented Decisions & Open Defaults

This document records product and technical decisions for Main Event Archive (MEA). Where the team has not explicitly chosen yet, we document **recommended defaults** so implementation can proceed. Change any default after discussion — do not assume silently.

## Decided (confirmed in planning)

| Topic | Decision |
|-------|----------|
| Launch catalog | WCW PPVs, 1993 forward |
| Video playback | Deferred to v1.6 (catalog-first v1–v1.5) |
| Per-match video | v1.7+; schema supports from v1 |
| Spoilers | Default off; hard vs soft tiers (see [spoiler-rules.md](../domain/spoiler-rules.md)) |
| Third-party ratings | Cagematch link-out badge only — no scores cached or displayed |
| Data seed | Wikidata + Wikipedia API (not scraping proprietary DBs) |
| Nitro match cards | Sourced from [prowrestling.fandom.com](https://prowrestling.fandom.com) via MediaWiki API (CC BY-SA 3.0); attributed on show pages + Attribution page. TV episodes have no individual Wikipedia results pages, so Fandom fills the gap. Imported via `shows:import-nitro-cards`. |
| Content maintenance | Staff admin UI only (no public submissions in v1) |
| Video rights / third-party links | Link-out only; report-and-remove for disputed URLs; same policy for all providers; archive.org secondary and droppable if recurring issues — see [video-linking-policy.md](video-linking-policy.md) (planned, not implemented) |

## Recommended defaults (implement unless changed)

### Guest vs authenticated

| Feature | Guest | Authenticated |
|---------|-------|---------------|
| Browse catalog | Yes | Yes |
| Search | Yes | Yes |
| Spoiler toggle | Yes | Yes |
| Rate shows/matches | No | Yes |
| Watchlist | No | Yes |
| Mark watched | No | Yes |

**Persistence:** Spoiler preference in session for guests; in `users.spoilers_enabled_default` (or similar) for logged-in users. Per-show spoiler reveal stored in session per show visit.

### Auth

- **Method:** Laravel Breeze with Inertia + React, email/password
- **Email verification:** Required before first rating
- **Roles:** `is_admin` boolean on `users` for v1 (no permissions package yet)

### Ratings

- **Scale:** 1–5 integer stars
- **Scope:** Show-level and match-level ratings in v1
- **Rules:** One rating per user per entity; upsert on change
- **Display:** Average + count always visible (not treated as spoilers)
- **Aggregation:** Simple average for v1; consider Bayesian average later for ranking

### Wrestlers (v1)

- **Default:** Participant names as strings on `match_participants` for v1 — no wrestler profile pages
- **Search:** Full-text on participant names on the card
- **v2+:** First-class `Wrestler` entity with deduplication and profile pages if needed

### Admin panel

- **Default:** Filament for staff CRUD (import review queue, show curation, spoiler flags)
- **Public app:** Inertia + React + Chakra UI
- **Rationale:** Fastest path for admin-only curation; avoids building two full admin UIs

### Match structure (v1)

- Tag/multi-person: multiple `match_participants` rows per side/group
- Non-match segments (promos): optional `match_type = segment`; not rateable in v1
- Dark matches: on card, rateable like other matches

### Show dates

- **Default:** `date` column as **date only** (no time) — wrestling show dates are historically inconsistent
- Store venue timezone in metadata only if needed later

### Spoiler system (implementation defaults)

- **Enforcement:** Server-side via `SpoilerContext` — never rely on client-only hiding
- **Per-show toggle:** User must opt in on each show page to see results/timestamps
- **Global default:** Optional site-wide preference for default spoiler state on new show visits
- **Deep links:** `?spoilers=1` on show URL may override for sharing; document in frontend conventions

### Search (v1)

- PostgreSQL full-text search on show title, promotion name, participant names
- Redis cache for browse listing pages
- Upgrade path to Laravel Scout documented in architecture

### Internationalization

- English-only v1; no i18n schema required initially

### Duplicate YouTube sources

- One canonical `Show` with multiple `Video` / `VideoSource` records ranked by staff (primary + fallbacks)

### Show card clickability & card availability indicator

- **Clickable rule (lenient):** A show is clickable to its detail page when it has **any** displayable detail — matches, video, **or** metadata (venue / city / rating / episode number). We do not block click-through just because a card is missing.
- **Card availability badge:** Browse/search cards show `Card` when match data exists and `No card` when it does not, mirroring the existing `Video` badge pattern in [`ShowCard.tsx`](../../resources/js/Components/ShowCard.tsx).
- **Card preview:** When a card exists, show the main event on the browse card. Spoiler-safe participant lines only.
- **Missing video indicator:** Still deferred — only positive `Video` badge today.

## Still TBD before specific features ship

- Exact Nitro year sub-scope within v1.4 (e.g. 1995–1998 first)
- Sentry / monitoring provider
- Dark mode default for Chakra theme

When in doubt, **ask** — see [AGENTS.md](../../AGENTS.md) and the `main-event-archive` skill.
