# Third-Party References

Policy for linking to external wrestling databases without republishing their data.

## Decision

Show a **badge that links out** (e.g. "View on Cagematch"). **Do not display or cache their rating data.**

## Allowed

| Action | Example |
|--------|---------|
| Outbound link badge | "View on Cagematch →" |
| Staff-pasted URL | `shows.cagematch_url` |
| Staff linker command | `vendor/bin/sail artisan shows:link-cagematch` — fetches listing pages to discover event URLs only; see below |
| `rel="noopener noreferrer"` on external links | Security + best practice |
| MEA community ratings | Only numeric scores on our site |

## Not allowed

| Action | Why |
|--------|-----|
| Display "7.3/10 on Cagematch" | Republishing their compiled data |
| Scrape or API poll for ratings | ToS / database rights |
| Cache their scores in Redis/DB | Same as displaying |
| Store Cagematch card data or ratings in MEA | Same as displaying |
| Add Cagematch to the public import pipeline | Automated seed stays Wikidata/Wikipedia only |
| Imply partnership without permission | Misleading |

## Implementation

```php
// shows table
cagematch_url: string|null  // staff-entered or staff linker command
```

### Staff exception: URL linker

`shows:link-cagematch` is a **staff-only curation aid**, not part of automated catalog seeding. It may HTTP-fetch Cagematch promotion listing pages to discover event page URLs and match them to MEA shows by date + title.

- **Stores:** outbound URL string in `shows.cagematch_url` only
- **Does not store:** ratings, votes, match cards, or other Cagematch page content
- **Does not overwrite:** shows that already have `cagematch_url`
- **Config:** [`config/cagematch.php`](../../config/cagematch.php) (promotion listing params)
- **Live fetch:** Cagematch may return HTTP 403 to server-side requests (WAF/captcha). When that happens, save listing page(s) from your browser and run:

```bash
vendor/bin/sail artisan shows:link-cagematch --html=storage/app/cagematch/wcw-page-1.html --dry-run
vendor/bin/sail artisan shows:link-cagematch --html-dir=storage/app/cagematch/wcw-ppv --from=1993 --to=1996
```

General scraping of Cagematch for ratings or card data remains prohibited.

Frontend: `CagematchBadge` component — icon + link text, no fetched metadata.

Same pattern may extend to ProFightDB or others via nullable URL fields — never import their ratings.

## Netflix link-out (WWE PPV)

MEA links out to Netflix for full-show viewing; we never embed or host Netflix playback.

| Mode | When | UX |
|------|------|-----|
| **Search fallback** | WWE PPV with no curated Netflix URL | "Watch on Netflix" opens Netflix search for the show title; subtitle: "Search Netflix for this event" |
| **Deep link** | `videos.provider = netflix` row on the show | "Watch on Netflix" opens `https://www.netflix.com/watch/{titleId}` |

### Allowed

- Outbound link buttons on show pages (`rel="noopener noreferrer"`)
- Staff-entered Netflix URLs in Filament (`videos` table)
- Staff import from **saved HTML** (browser save of Netflix browse/collection page while logged in):

```bash
vendor/bin/sail artisan videos:import-netflix --html=storage/app/netflix/wwe-ppv.html --promotion=wwe --dry-run
```

**Saving tips:** Wait for tiles to load before Ctrl+S. Search pages use `jbv=` IDs (supported); browse/collection pages use `/title/` links. MHTML (Chrome default) and HTML-only both work. Search pages list series without years — for 1996–2001 PPV deep links, save each PPV series detail page (Survivor Series, Royal Rumble, etc.) after scrolling through all available years.

- **Stores:** `provider`, `external_id` (Netflix title ID), `url` only
- **Does not store:** Netflix synopsis, artwork, availability flags, or regional catalog metadata
- **Does not overwrite:** existing Netflix rows unless `--force`

### Not allowed / not guaranteed

- Live automated scraping of Netflix (WAF, ToS, regional catalogs)
- Implying MEA verified a title is available on Netflix when using **search fallback** (best-effort search only)
- Displaying Netflix availability on browse cards (show page button only for v1.2)

Config: [`config/streaming.php`](../../config/streaming.php) (`NETFLIX_WWE_PPV_SEARCH_ENABLED`, search URL template).

### Brand assets (show page buttons)

Official PNGs from each platform’s brand kit. Do not recolor, stretch, or modify artwork. Black `#000000` button background matches Netflix guidance and YouTube dark-background logo usage. Link-out only — no partnership implied.

| File | Used in UI | Source |
|------|------------|--------|
| [`Netflix_Logo_RGB.png`](../../resources/images/third-party/Netflix_Logo_RGB.png) | `NetflixWatchButton` (wordmark) | [Netflix Brand Site → Logos](https://brand.netflix.com/en/assets/logos/) |
| [`Netflix_Symbol_RGB.png`](../../resources/images/third-party/Netflix_Symbol_RGB.png) | Alternate (symbol) | Same — available if a compact icon treatment is needed elsewhere |
| [`youtube-logo-full-white.png`](../../resources/images/third-party/youtube-logo-full-white.png) | `YouTubeWatchButton` | [YouTube Brand Resources](https://www.youtube.com/howyoutubeworks/resources/brand-resources/) — white full logo for dark backgrounds |

Frontend: `NetflixWatchButton` uses the **wordmark** on black. Accessible name stays in `aria-label`; trust copy below the button names the platform.

### Promotion logos

Sourced from [Wikimedia Commons](https://commons.wikimedia.org/) where tagged public domain (PD-textlogo). **Trademark rights may still apply** — use is nominative/descriptive in a wrestling catalog context only; no partnership implied.

| File | Used in UI | Commons source | License notes |
|------|------------|----------------|---------------|
| [`promotions/wcw.svg`](../../resources/images/promotions/wcw.svg) | `PromotionLogo` (WCW) — white tile | [File:Wcw_logo.svg](https://commons.wikimedia.org/wiki/File:Wcw_logo.svg) | PD-textlogo; trademark may apply |
| [`promotions/wwe.svg`](../../resources/images/promotions/wwe.svg) | `PromotionLogo` (WWE) — black tile | [File:WWE_official_logo.svg](https://commons.wikimedia.org/wiki/File:WWE_official_logo.svg) | PD-textlogo; trademark may apply |
| [`promotions/aew.svg`](../../resources/images/promotions/aew.svg) | `PromotionLogo` (AEW) — white tile | [File:All Elite Wrestling logo 2024.svg](https://commons.wikimedia.org/wiki/File:All_Elite_Wrestling_logo_2024.svg) | PD-textlogo; trademark may apply |
| [`promotions/tna.svg`](../../resources/images/promotions/tna.svg) | `PromotionLogo` (TNA) — **black tile** (gold mark; poor contrast on white) | [File:TNA Wrestling (2024) Logo.svg](https://commons.wikimedia.org/wiki/File:TNA_Wrestling_(2024)_Logo.svg) | PD-textlogo; trademark may apply |
| [`promotions/ecw.svg`](../../resources/images/promotions/ecw.svg) | `PromotionLogo` (ECW) — white tile | [File:ECW logo 2001.svg](https://en.wikipedia.org/wiki/File:ECW_logo_2001.svg) (English Wikipedia) | Wikipedia-hosted logo; trademark may apply |

Used on the promotions index, browse/show cards, home carousel, and match rows (via [`PromotionLogo`](../../resources/js/Components/PromotionLogo.tsx)). Curated promotion descriptions are staff/config authored for v1; `wikipedia_url` is link-out only until a Wikipedia import command exists.

## YouTube link-out

MEA links out to YouTube for full-show viewing when a show-level YouTube video exists; we never host video.

- **UI:** YouTube full logo on black button (matches Netflix pattern) + “Opens on YouTube.com · new tab” trust line
- **Assets:** white full logo PNG from [YouTube Brand Resources](https://www.youtube.com/howyoutubeworks/resources/brand-resources/) at `resources/images/third-party/youtube-logo-full-white.png`
- **Allowed:** outbound link with `rel="noopener noreferrer"`; store `provider`, `external_id`, `url` on `videos` only
- **Staff entry:** Filament show **Videos** relation (platform selector) or `videos:sync-youtube-playlist`
- **Not allowed:** implying YouTube partnership; caching YouTube metadata beyond link fields

See [`docs/architecture/video-providers.md`](../architecture/video-providers.md).

## Multi-platform “Where to watch”

Shows may have **multiple show-level `videos` rows** — typically one per platform (e.g. Netflix deep link + YouTube full event). The public show page uses `watch_targets[]`, not a single video URL.

| Layer | Behavior |
|-------|----------|
| **Database** | `Show` `hasMany` `Video`; unique on `(provider, external_id)` allows one Netflix row and one YouTube row per show |
| **Resolver** | [`WatchTargetResolver`](../../app/Services/Streaming/WatchTargetResolver.php) returns YouTube first (when curated), then Netflix deep link or WWE search fallback |
| **UI** | [`VideoPlaceholder`](../../resources/js/Components/VideoPlaceholder.tsx) renders a button per target side-by-side |
| **`is_primary`** | Per platform — marks the preferred link when multiple rows share the same `provider` |

Import pipelines are independent: `videos:import-netflix` and `videos:sync-youtube-playlist` upsert their own provider without removing the other.

**Example dual-source show:** `vengeance-2001` (Netflix curated + YouTube link when staff/sync adds one).

## Legal note

Link-out without republishing is low-risk. Displaying third-party scores would require permission. Not legal advice.

## Related docs

- [Data model](data-model.md)
- [Data import](data-import.md) — why we don't import from Cagematch
- [Vision](../product/vision.md)
