# MVP Scope & Roadmap

## Implementation roadmap

Incremental catalog expansion first; **video playback at v1.6**.

| Version | Scope | User-facing notes |
|---------|-------|-------------------|
| **v1** | WCW PPVs, 1993 forward | Browse, search, spoilers, MEA ratings, watchlist, watched |
| **v1.1** | WCW pre-1993 PPVs + Clash of the Champions | Match timestamp **admin entry** (not user-facing until v1.6) |
| **v1.2** | WWE PPVs pre-2001 | No brand split |
| **v1.3** | WWE PPVs with brand split | Raw/SmackDown on shows (2002+ era) |
| **v1.4** | WCW TV — Nitro only | Weekly volume; consider year sub-scope |
| **v1.5** | WWE TV — Raw and SmackDown only | Brand-aware TV |
| **v1.6** | YouTube linking (staff + AI) | Full-show embed/link; timestamps live for users |
| **v1.7+** | Per-match video linking | When full show unavailable on YouTube |

## v1 must ship

### Catalog

- [ ] WCW PPV import filter: `promotion = WCW`, `show_type = PPV`, `date >= 1993-01-01`
- [ ] Staff review queue: draft → pending_review → published
- [ ] Provenance on imported shows

### Public features

- [ ] Home, browse (promotion/year/type), search, show detail
- [ ] Spoiler-safe show page (default off; per-show reveal)
- [ ] MEA ratings: show + match (1–5 stars, auth required)
- [ ] Watchlist + watched (auth required)
- [ ] Cagematch link-out badge when `cagematch_url` set (no their scores)
- [ ] Video placeholder on show page ("Video linking coming soon" / no player)

### Auth

- [ ] Breeze (Inertia + React): register, login, email verification
- [ ] Guests: browse, search, spoiler toggle

### Admin

- [ ] Filament: show/match CRUD, import review, spoiler flags, external URLs

### Infrastructure

- [ ] PostgreSQL + Redis in dev (Sail)
- [ ] Nullable `videos` schema; spoiler-safe API transformers
- [ ] Wikidata-first import command (Wikipedia enrichment optional)

## v1 explicitly out of scope

- YouTube embed / playback
- User-facing match timestamps
- WWE, TV shows, brands UI
- Per-match video linking
- AI enrichment
- Public user submissions
- Wrestler profile pages

## Positioning copy (v1–v1.5)

> Catalog is live — browse cards, rate shows, build your watchlist. Video linking is coming in a future update.

## v1.1+ backlog (summary)

See [decisions.md](decisions.md) and [../domain/data-import.md](../domain/data-import.md) for import expansion, [../domain/ai-enrichment.md](../domain/ai-enrichment.md) for v1.6 video/AI work.

## Related docs

- [Vision](vision.md)
- [User stories](user-stories.md)
- [Data model](../domain/data-model.md)
