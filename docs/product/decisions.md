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
| Content maintenance | Staff admin UI only (no public submissions in v1) |

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

## Still TBD before specific features ship

- Exact Nitro year sub-scope within v1.4 (e.g. 1995–1998 first)
- Sentry / monitoring provider
- Dark mode default for Chakra theme

When in doubt, **ask** — see [AGENTS.md](../../AGENTS.md) and the `main-event-archive` skill.
