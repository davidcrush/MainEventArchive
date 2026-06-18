# Initial Catalog Seeding

Step-by-step guide for bootstrapping the show catalog on a fresh environment (local Sail or Laravel Forge production). Use this when Wikidata coverage is sparse or unavailable and you need show rows before staff review and publish.

## What this covers

| In scope | Out of scope (for now) |
|----------|-------------------------|
| Show records (title, date, slug, type) | User accounts beyond admin |
| Curated WCW PPV, Clash, and Nitro catalogs | Full database restore |
| WWE PPV catalog (1996–2001) | |
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

Run these **in order** for WCW. Each creates or updates shows; all new rows start as `pending_review`.

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

### 4. WCW Monday Nitro (full run, 1995–2001)

Weekly TV episodes (`show_type = tv`). The full episode catalog is seeded from Fandom's `Template:WCW Nitro results` navbox — one `pending_review` shell per episode, numbered chronologically from the Sep 4, 1995 premiere (#1). The command is idempotent (matched by air date) and safe to re-run.

```bash
vendor/bin/sail artisan shows:seed-nitro-catalog --promotion=wcw
# Optional scoping by year:
vendor/bin/sail artisan shows:seed-nitro-catalog --from=1997 --to=2001
vendor/bin/sail artisan shows:seed-nitro-catalog --dry-run
```

Then fill match cards and venue/city via Fandom (see *Nitro — match cards from Fandom* below):

```bash
vendor/bin/sail artisan shows:import-nitro-cards --promotion=wcw
```

> The legacy offline seeder `WcwNitroCatalogSeeder` (curated `database/seeders/data/wcw_nitro_1996.php`, 1996 only) still works for an offline 1996 bootstrap, but `shows:seed-nitro-catalog` is now the canonical, full-run source and supersedes it.

### One-liner (local or prod)

After migrations and admin seed:

```bash
php artisan db:seed --class=WcwPre1990PpvCatalogSeeder --force && \
php artisan db:seed --class=WcwPpvCatalogSeeder --force && \
php artisan db:seed --class=WcwClashCatalogSeeder --force && \
php artisan db:seed --class=WcwNitroCatalogSeeder --force
```

(Add `vendor/bin/sail` before `php` when running inside Sail locally.)

### 5. WWE PPV catalog

Built from bundled Cagematch saves (merged by air date, no duplicates):

| File | Years |
|------|-------|
| `docs/third-party/cagematch/WWE-PPVs-1996-1985.mhtml` | 1985–1996 |
| `docs/third-party/cagematch/WWE-PPVs-2003-1996.html` | 1996–2003 |
| `docs/third-party/cagematch/WWE-PPVs-2010-2003.mhtml` | 2003–2010 |
| `docs/third-party/cagematch/WWE-PPVs-2016-2010.mhtml` | 2010–2016 |
| `docs/third-party/cagematch/WWE-PPVs-2021-2016.mhtml` | 2016–2021 |
| `docs/third-party/cagematch/WWE-PPVs-2026-2021.mhtml` | 2021–2026 |

Creates PPV rows for slug `wwe` (**World Wrestling Entertainment**). Cagematch `WWF` prefixes are stripped from stored titles (e.g. `Royal Rumble 1996`).

**Canonical command** (scoped year range, dry-run supported):

```bash
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=1996 --to=2001
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=2002 --to=2010
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=2011 --to=2020
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=2021 --to=2026
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=1985 --to=1995
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=2011 --to=2020 --dry-run
```

Legacy seeder (defaults to 1996–2001, same importer):

```bash
vendor/bin/sail artisan db:seed --class=WwePpvCatalogSeeder
```

**Caution:** Delete is **scoped to the requested year range** — only WWE PPV shows in `--from`/`--to` that are **not** in the merged Cagematch list are removed. Seeding 2002–2010 does not touch published 1996–2001 rows.

Wikipedia page title overrides (when import fails): `database/seeders/data/wwe_ppv_overrides.php`.

## Optional enrichment (after show rows exist)

Run these on the same environment when you want metadata beyond the curated seed files. Skip any step you do not need yet.

### Wikidata (when API is working)

Creates PPV records with dates, venues, and provenance:

```bash
vendor/bin/sail artisan shows:import wikidata --from=1993 --to=1996
```

If Wikidata returns nothing for your range, use the seeders above — that is the expected fallback for WCW v1.

### WCW PPVs — Wikipedia match cards

```bash
vendor/bin/sail artisan shows:import wikipedia --promotion=wcw --from=1993 --to=2001
```

Single show by slug (promotion optional):

```bash
vendor/bin/sail artisan shows:import wikipedia starrcade-1996
```

### Clash — Wikipedia match cards and venue text

```bash
vendor/bin/sail artisan shows:import wikipedia --promotion=wcw --from=1989 --to=1997
```
```

Wikipedia import **replaces all matches** on a show when re-run. Parser edge cases: [wikipedia-parser.md](wikipedia-parser.md).

### Nitro — Nielsen ratings and notable-episode fields

```bash
vendor/bin/sail artisan shows:import-nitro-metadata
```

Merges sparse TV ratings and venue/city from Wikipedia’s notable-episodes section by air date.

### Nitro — match cards from Fandom

TV episodes have no individual Wikipedia results pages, so match cards come from [prowrestling.fandom.com](https://prowrestling.fandom.com) (MediaWiki API, CC BY-SA 3.0). Pages are resolved by air date (`"{F j, Y} Monday Nitro results"`).

```bash
vendor/bin/sail artisan shows:import-nitro-cards --promotion=wcw
# Optional scoping:
vendor/bin/sail artisan shows:import-nitro-cards --from=1996-01-01 --to=1996-12-31
vendor/bin/sail artisan shows:import-nitro-cards --identifier=<show-slug>
vendor/bin/sail artisan shows:import-nitro-cards --dry-run
```

Only enriches Nitro shows already in the catalog (seed them first with `shows:seed-nitro-catalog`). Cards are stored with spoiler-safe participant ordering, dark matches flagged non-rateable, and draws stored with `winner_side = null`. Venue/city are captured from the episode infobox in the same pass (only when currently empty, so curated values are never overwritten). If the parsed match count disagrees with the page's declared bullet count, the show is **skipped** (left for review) so partial/leaky cards are never stored. Sets `source = fandom` and links the source page; the show page renders a Fandom attribution badge.

### Venues — structured `venues` rows

```bash
vendor/bin/sail artisan shows:import-venues --from=1993 --to=2001
```

See [wikipedia-venue-parser.md](wikipedia-venue-parser.md). Multi-venue events stay as comma-joined text on the show only.

### WWE PPVs — Wikipedia match cards

After seeding:

```bash
# Initial era (1996–2001)
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=1996 --to=2001
vendor/bin/sail artisan shows:import wikipedia --promotion=wwe --from=1996 --to=2001
vendor/bin/sail artisan shows:import-venues --promotion=wwe --from=1996 --to=2001

# Expansion era (2002–2010)
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=2002 --to=2010
vendor/bin/sail artisan shows:import wikipedia --promotion=wwe --from=2002 --to=2010 --workers=4
vendor/bin/sail artisan shows:import-venues --promotion=wwe --from=2002 --to=2010
vendor/bin/sail artisan shows:verify-wikipedia --promotion=wwe --from=2002 --to=2010

# Expansion era (2011–2020) — includes NXT TakeOver events on Cagematch listing
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=2011 --to=2020
vendor/bin/sail artisan shows:import wikipedia --promotion=wwe --from=2011 --to=2020 --workers=4
vendor/bin/sail artisan shows:import-venues --promotion=wwe --from=2011 --to=2020
vendor/bin/sail artisan shows:verify-wikipedia --promotion=wwe --from=2011 --to=2020

# Expansion era (2021–2026) — WrestleMania preferred over NXT Stand & Deliver on same date
vendor/bin/sail artisan shows:seed-wwe-ppv-catalog --from=2021 --to=2026
vendor/bin/sail artisan shows:import wikipedia --promotion=wwe --from=2021 --to=2026 --workers=4
vendor/bin/sail artisan shows:import-venues --promotion=wwe --from=2021 --to=2026
vendor/bin/sail artisan shows:verify-wikipedia --promotion=wwe --from=2021 --to=2026
```

Follow-up backfill (1985–1995): same commands with `--from=1985 --to=1995`.

Browse at `/browse?promotion=wwe`. Bulk Wikipedia import **requires** `--promotion` so WCW and WWE shows in the same year range are not cross-enriched.

WWE PPV show pages show **Watch on Netflix** (search for the show title) when no YouTube link exists. Browse `platform=netflix` and the **Video** badge include published WWE PPVs without stored Netflix rows.

### YouTube (dry-run first)

```bash
vendor/bin/sail artisan videos:sync-youtube-playlist --promotion=wcw --playlist=wcw_clash --dry-run
vendor/bin/sail artisan videos:sync-youtube-playlist --promotion=wcw --playlist=wcw_nitro --dry-run
vendor/bin/sail artisan videos:sync-youtube-playlist --promotion=wwe --playlist=wwe_ppv --dry-run
vendor/bin/sail artisan videos:sync-youtube-playlist --promotion=wwe --playlist=wwe_nxt --dry-run
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
| `WwePpvCatalogSeeder` / `shows:seed-wwe-ppv-catalog` | PPV | Date (+ promotion) | Yes — PPVs in requested `--from`/`--to` range not in merged Cagematch list |

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
