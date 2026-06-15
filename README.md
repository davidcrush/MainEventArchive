# Main Event Archive

[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2Fe7ed00e8-2b04-40f7-ab99-738acc2c6aaa%3Fdate%3D1%26label%3D1%26commit%3D1&style=flat-square)](https://forge.laravel.com/david-crush/107173250220/3243142)

A spoiler-safe pro wrestling show catalog — find cards, rate shows, build your watchlist. Video linking via YouTube (and future providers) arrives in v1.6; we never host video.

## Documentation

**Start here:** [docs/README.md](docs/README.md)

| Topic | Link |
|-------|------|
| Product vision | [docs/product/vision.md](docs/product/vision.md) |
| v1 scope | [docs/product/mvp-scope.md](docs/product/mvp-scope.md) |
| Data model | [docs/domain/data-model.md](docs/domain/data-model.md) |
| Agent guidelines | [AGENTS.md](AGENTS.md) |

## Stack

- Laravel 13, PHP 8.5
- PostgreSQL, Redis
- React + Inertia + Chakra UI (planned for public UI)
- Laravel Sail for development

## Development

Requires Docker. All commands through Sail:

```bash
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
```

Open the app: `vendor/bin/sail open`

Frontend (when installed): `vendor/bin/sail npm run dev`

## Seeding

After migrations, seed the Filament admin account and (optionally) load the WCW PPV catalog.

### Admin user

Set credentials in `.env` (see `.env.example`). `ADMIN_PASSWORD` must be changed from the placeholder or seeding will fail.

```bash
ADMIN_NAME="Your Name"
ADMIN_EMAIL=you@example.com
ADMIN_PASSWORD=your-strong-password
```

Create or update the admin user:

```bash
vendor/bin/sail artisan db:seed --class=AdminUserSeeder
```

Log in at `/admin` with `ADMIN_EMAIL` and `ADMIN_PASSWORD`.

`vendor/bin/sail artisan db:seed` runs the full `DatabaseSeeder`, which also creates a single sample show (Starrcade 1997) for local smoke testing.

### Catalog: shows and matches

Catalog import is a two-step pipeline. Imported shows land in `pending_review` until staff publishes them in Filament. See [docs/domain/data-import.md](docs/domain/data-import.md) and [docs/domain/admin-workflow.md](docs/domain/admin-workflow.md).

**1. Show metadata (Wikidata)** — creates WCW PPV records with dates, venues, and provenance:

```bash
vendor/bin/sail artisan shows:import wikidata --from=1993 --to=1996
```

Adjust `--from` and `--to` for the year range you want. Requires network access to the Wikidata API.

**Fallback catalog** — if Wikidata coverage is sparse, seed a static WCW PPV list (shows only, no match cards):

```bash
vendor/bin/sail artisan db:seed --class=WcwPpvCatalogSeeder
```

**Pre-1990 catalog (1983–1989)** — Wikidata has no WCW PPV rows for this era; seed the curated list instead:

```bash
vendor/bin/sail artisan db:seed --class=WcwPre1990PpvCatalogSeeder
```

Then enrich with Wikipedia using `--from=1983 --to=1990` (see steps 2–3 below). Shows land in `pending_review` until staff publishes them in Filament.

**Clash of the Champions (1989–1997)** — TV specials (`show_type = tv`); Wikidata has no coverage. Seed the curated catalog, then enrich via Wikipedia:

```bash
vendor/bin/sail artisan db:seed --class=WcwClashCatalogSeeder
vendor/bin/sail artisan shows:import wikipedia --from=1989 --to=1997
vendor/bin/sail artisan videos:sync-youtube-playlist --promotion=wcw --playlist=wcw_clash --dry-run
```

Browse Clash shows with **Show Type: TV** on `/browse`. Match timestamps are entered manually in Filament (v1.1).

**WCW Monday Nitro (1996)** — weekly TV (`show_type = tv`); no Wikipedia episode list or match cards in v1. Seed curated catalog, enrich Nielsen ratings from Wikipedia notable episodes, then sync YouTube:

```bash
vendor/bin/sail artisan db:seed --class=WcwNitroCatalogSeeder
vendor/bin/sail artisan shows:import-nitro-metadata
vendor/bin/sail artisan videos:sync-youtube-playlist --promotion=wcw --playlist=wcw_nitro --dry-run
```

Browse Nitro with **Show Type: TV** on `/browse`. Staff publish in Filament after review. Re-run YouTube sync as WCW uploads more full episodes.

**2. Match cards (Wikipedia)** — enriches existing shows with participants and results. Run after step 1 (or the fallback seeder):

```bash
# One show by slug
vendor/bin/sail artisan shows:import wikipedia starrcade-1996

# All shows in a year range
vendor/bin/sail artisan shows:import wikipedia --from=1993 --to=1996
```

Wikipedia import replaces all matches on a show when re-run. Parser edge cases are documented in [docs/domain/wikipedia-parser.md](docs/domain/wikipedia-parser.md).

**3. Venues (Wikipedia)** — links single-venue shows to structured `venues` rows (location, capacity, historical names). Run after Wikipedia show enrichment, or rely on automatic linking during step 2:

```bash
vendor/bin/sail artisan shows:import-venues --from=1993 --to=2001
```

See [docs/domain/wikipedia-venue-parser.md](docs/domain/wikipedia-venue-parser.md).

### Known limitations

- **Multi-venue events** (e.g. WrestleMania 2 at three arenas) stay as comma-joined text on the show record only — no `venue_id`. Fine for WCW v1; must be revisited before **WWE ingest (v1.2+)**.

## Design assets

Place UX mocks in `docs/design/mocks/` and source logo in `docs/design/brand/`. See [docs/design/README.md](docs/design/README.md).

## v1 focus

WCW PPVs from 1993 forward — catalog, spoilers, ratings, watchlist. No video playback until v1.6.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
