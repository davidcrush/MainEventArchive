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

## Legal note

Link-out without republishing is low-risk. Displaying third-party scores would require permission. Not legal advice.

## Related docs

- [Data model](data-model.md)
- [Data import](data-import.md) — why we don't import from Cagematch
- [Vision](../product/vision.md)
