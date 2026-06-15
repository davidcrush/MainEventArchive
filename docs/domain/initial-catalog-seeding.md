# Initial Catalog Seeding

Step-by-step guide for bootstrapping the WCW show catalog on a fresh environment (local Sail or Laravel Forge production). Use this when Wikidata coverage is sparse or unavailable and you need show rows before staff review and publish.

## What this covers

| In scope | Out of scope (for now) |
|----------|-------------------------|
| Show records (title, date, slug, type) | User accounts beyond admin |
| Curated PPV, Clash, and Nitro catalogs | Full database restore |
| Optional Wikipedia / YouTube enrichment | Match cards unless you run Wikipedia import |
| Staff publish workflow | Ongoing day-to-day curation |

Imported and seeded shows land in **`pending_review`** until staff publishes them in Filament. See [admin-workflow.md](admin-workflow.md).

## Recommended approach: run seeders on the target environment

**Do not copy your local PostgreSQL dump to production** unless you have Filament state (published flags, manual edits) that seeders cannot recreate. For a typical first deploy, run the same Artisan seeders on Forge that you ran locally.

| Approach | Best for | Risk |
|----------|----------|------|
| **Seeders (recommended)** | First prod catalog, repeatable deploys, Wikidata unavailable | Re-publish shows in Filament |
| **Selective `pg_dump` (`promotions` + `shows` only)** | Many shows already published locally; manual edits not in seed files | FK / ID mismatches if merged carelessly |
| **Full database restore** | Empty greenfield prod with nothing to lose | Wipes users, sessions, ratings, watchlists |

Seed data lives in the repo (`database/seeders/` and `docs/third-party/cagematch/`), so production can always be rebuilt from git without SCP.

## Prerequisites

1. **Migrations applied**

   Local:

   ```bash
   vendor/bin/sail artisan migrate
   ```

   Production (Forge SSH or deploy script):

   ```bash
   php artisan migrate --force
   ```

2. **Admin user** — set `ADMIN_NAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` in `.env` (password must not be the placeholder). Then:

   Local:

   ```bash
   vendor/bin/sail artisan db:seed --class=AdminUserSeeder
   ```

   Production:

   ```bash
   php artisan db:seed --class=AdminUserSeeder --force
   ```

   Log in at `/admin`.

3. **Do not run `DatabaseSeeder` on production** — it creates dev smoke data (a sample Starrcade 1997 row). Use the catalog seeders below instead.

## Catalog seed order

Run these **in order**. Each creates or updates WCW shows; all new rows start as `pending_review`.

Local commands use Sail; on Forge, drop the `vendor/bin/sail` prefix.

### 1. Pre-1990 PPVs (1983–1989)

Wikidata has no WCW PPV rows for this era. Curated list in `database/seeders/data/wcw_pre1990_ppvs.php`.

```bash
vendor/bin/sail artisan db:seed --class=WcwPre1990PpvCatalogSeeder
```

### 2. PPV catalog (1990–2001)

Built from bundled Cagematch listing HTML (`docs/third-party/cagematch/WCW-PPV.html`). Creates one PPV per event date in range.

```bash
vendor/bin/sail artisan db:seed --class=WcwPpvCatalogSeeder
```

**Caution:** This seeder **deletes** WCW PPV shows dated 1990–2001 that are **not** in the bundled Cagematch list. Do not run it if production has extra manual PPVs in that range you want to keep.

### 3. Clash of the Champions (1989–1997)

TV specials (`show_type = tv`). Curated list in `database/seeders/data/wcw_clash_catalog.php`.

```bash
vendor/bin/sail artisan db:seed --class=WcwClashCatalogSeeder
```

### 4. WCW Monday Nitro (1996)

Weekly TV episodes (`show_type = tv`). Curated list in `database/seeders/data/wcw_nitro_1996.php`. No match cards in v1.

```bash
vendor/bin/sail artisan db:seed --class=WcwNitroCatalogSeeder
```

### One-liner (local or prod)

After migrations and admin seed:

```bash
php artisan db:seed --class=WcwPre1990PpvCatalogSeeder --force && \
php artisan db:seed --class=WcwPpvCatalogSeeder --force && \
php artisan db:seed --class=WcwClashCatalogSeeder --force && \
php artisan db:seed --class=WcwNitroCatalogSeeder --force
```

(Add `vendor/bin/sail` before `php` when running inside Sail locally.)

## Optional enrichment (after show rows exist)

Run these on the same environment when you want metadata beyond the curated seed files. Skip any step you do not need yet.

### Wikidata (when API is working)

Creates PPV records with dates, venues, and provenance:

```bash
vendor/bin/sail artisan shows:import wikidata --from=1993 --to=1996
```

If Wikidata returns nothing for your range, use the seeders above — that is the expected fallback for WCW v1.

### Clash — Wikipedia match cards and venue text

```bash
vendor/bin/sail artisan shows:import wikipedia --from=1989 --to=1997
```

Wikipedia import **replaces all matches** on a show when re-run. Parser edge cases: [wikipedia-parser.md](wikipedia-parser.md).

### Nitro — Nielsen ratings and notable-episode fields

```bash
vendor/bin/sail artisan shows:import-nitro-metadata
```

Merges sparse TV ratings and venue/city from Wikipedia’s notable-episodes section by air date.

### Venues — structured `venues` rows

```bash
vendor/bin/sail artisan shows:import-venues --from=1993 --to=2001
```

See [wikipedia-venue-parser.md](wikipedia-venue-parser.md). Multi-venue events stay as comma-joined text on the show only.

### YouTube (dry-run first)

```bash
vendor/bin/sail artisan videos:sync-youtube-playlist --promotion=wcw --playlist=wcw_clash --dry-run
vendor/bin/sail artisan videos:sync-youtube-playlist --promotion=wcw --playlist=wcw_nitro --dry-run
```

Remove `--dry-run` after reviewing matches. Requires playlist IDs in `.env` — see [.env.example](../../.env.example).

## Publish in Filament

1. Open `/admin` → **Shows**.
2. Use tabs **Pending review**, **Nitro**, or **TV** to find imported rows.
3. Review title, date, and type; edit if needed.
4. Click **Publish** on each show (or from the edit page).

Published shows appear on `/browse` immediately; browse cache is invalidated on publish (see [caching.md](../architecture/caching.md)).

Browse filters:

- **PPVs** — Show Type: PPV (default)
- **Clash / Nitro** — Show Type: TV

## Production (Forge) checklist

1. Deploy latest code (seed files must be on the server).
2. `php artisan migrate --force`
3. `php artisan db:seed --class=AdminUserSeeder --force` (if not already done)
4. Run the four catalog seeders (see [Catalog seed order](#catalog-seed-order))
5. Optional: enrichment commands (Wikipedia, Nitro metadata, YouTube)
6. Publish shows in Filament
7. Confirm `/browse` and a few show pages on the live site

Forge one-off commands: **Site → Commands** or SSH into the server. No Sail on production.

## Alternative: copy only `shows` from local

Use this only if seeders cannot reproduce what you need — mainly **published status** or Filament edits not reflected in seed files.

**Local export** (Sail PostgreSQL):

```bash
vendor/bin/sail exec pgsql pg_dump -U sail -d laravel \
  -t promotions -t shows --data-only --column-inserts > shows-export.sql
```

**Before import on production:**

1. Back up the production database.
2. Ensure a `promotions` row with `slug = wcw` exists (or import both tables together so FKs match).
3. Prefer importing into an empty catalog; merging with existing show IDs is error-prone.

**Avoid a full `pg_dump` restore** on a live Forge site — it overwrites users, cache keys, and any production-only data.

## Seeder behavior reference

| Seeder | Show type | Match by | Deletes extras? |
|--------|-----------|----------|-----------------|
| `WcwPre1990PpvCatalogSeeder` | PPV | Date (+ promotion) | No |
| `WcwPpvCatalogSeeder` | PPV | Date (1990–2001) | Yes — PPVs in range not in Cagematch list |
| `WcwClashCatalogSeeder` | TV | Date (+ promotion) | No |
| `WcwNitroCatalogSeeder` | TV (Nitro titles) | Date (+ Nitro title prefix) | No |

Re-running seeders is safe for Clash, Nitro, and Pre-1990: they update titles/slugs when curated data changes. Existing **published** status is not reset on update — only **new** rows are created as `pending_review`.

## Troubleshooting

| Problem | What to do |
|---------|------------|
| Wikidata import returns no rows | Use catalog seeders; Wikidata WCW PPV coverage is incomplete for v1 |
| Shows not on public site | Check `status = published` in Filament |
| Nitro not visible in admin | Use the **Nitro** tab on the Shows list |
| Browse still stale after publish | Publish triggers cache invalidation; hard-refresh the browser |
| `WcwPpvCatalogSeeder` removed a show | Show date was in 1990–2001 but not in bundled Cagematch HTML; re-add manually or fix seed data |

## Related docs

- [Data import](data-import.md) — import architecture and licensing
- [Admin workflow](admin-workflow.md) — review queue and publish lifecycle
- [Data model](data-model.md) — schema source of truth
- [Development environment](../development-environment.md) — Sail setup
- [Caching](../architecture/caching.md) — browse cache invalidation
