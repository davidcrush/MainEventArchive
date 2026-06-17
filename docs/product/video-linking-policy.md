# Video Linking Policy

**Status:** Planned — documents intended approach for v1.6+ video work. Not implemented yet.

## Summary

Main Event Archive is a **catalog and watch guide**. We link to third-party video; we never host, store, or serve video files. Catalog metadata (show cards, dates, ratings) is separate from video pointers.

This policy applies to **all** external video providers (YouTube, archive.org, and any future `VideoProvider` adapter) — not only Internet Archive.

## What we do and do not do

| We do | We do not |
|-------|-----------|
| Staff-curate external URLs on show/match pages | Host or embed files we control |
| Link or embed (when allowed) via `VideoProvider` | Scrape or auto-import video URLs from archive.org or similar |
| Remove MEA links promptly when rights are disputed | Investigate or adjudicate whether a third-party copy is legal |
| Index show metadata from open data (Wikidata, Wikipedia) | Imply partnership with promotions or rights holders |

## Third-party rights assumptions

**Being on archive.org (or YouTube) does not mean content is public domain or freely licensed.**

- Licenses on archive.org are **uploader-declared**; Internet Archive does not guarantee copyright status.
- Full pro wrestling broadcasts (WCW, WWE, etc.) are typically **copyrighted**. A Creative Commons badge on an item does not prove the uploader had authority to grant rights.
- Prefer **official or clearly legitimate** sources when staff curate links (promotion channels, licensed uploads). Treat fan rips and ambiguous IA items as higher risk.

See [video-providers.md](../architecture/video-providers.md) for technical adapter design. YouTube remains first per [ADR 001](../architecture/decisions/001-youtube-first.md); archive.org is a **secondary** provider to evaluate later.

## Rights reports and link removal

When video linking ships (v1.6+), provide a path for anyone — especially rights holders — to report a problematic link.

### Public-facing stance

- Copy: **“Report a rights concern — we’ll review and remove the link promptly.”**
- Do not promise automatic takedown on anonymous reports or that MEA can remove content from the host site (only our link).

### Report flow (target)

1. **Entry point:** “Report this link” on show pages where a video URL is shown; optional email/contact on a `/copyright` or `/attribution` section.
2. **Submission:** URL, show (or match), reason, reporter contact.
3. **Review:** Staff verify the link is still attached; prioritize credible rights-holder claims (company domain, detailed work identification).
4. **Action:** Remove or disable the `Video` row on MEA; do not re-add the same URL without staff override.
5. **Response:** Confirm removal to reporter. Point them to the host’s own process (e.g. Internet Archive DMCA) if they need the file removed at source.

### What we remove

We remove **pointers in our database** only. The file on archive.org or YouTube remains until that platform acts.

### Audit

Log who added a link, removal reason, and date — supports repeat-offender policy and import job guards (e.g. do not resurrect `removed_rights` URLs).

## Provider circuit breaker

If a provider creates disproportionate rights friction, pause or drop it.

**Example thresholds (tune at implementation):**

| Signal | Response |
|--------|----------|
| First credible rights report | Remove link; review how it was added |
| Multiple credible reports in a short window | Pause new links from that provider pending review |
| Sustained pattern or direct legal contact | Stop supporting that provider in UI and config |
| Mass link rot (host DMCA sweeps) | Deprioritize provider in curation; fall back to alternatives |

archive.org is explicitly **experimental / secondary**. Willingness to rip it out entirely if it becomes a recurring issue is part of this policy.

## Future implementation checklist

When ready to build (not before v1.6 video milestone unless policy pages ship earlier):

- [ ] Public policy section (`/copyright` or extend [Attribution](../../resources/js/Pages/Attribution.tsx))
- [ ] “Report this link” on show detail when `Video` is present
- [ ] Filament or internal queue for rights reports
- [ ] `Video` status or flag for rights-based removal (e.g. `removed_rights`) to block re-import
- [ ] Same report path for YouTube and any other provider
- [ ] Document SLA target (e.g. review within 48–72 business hours)

## Related docs

- [Vision](vision.md) — no self-hosting, ethical sourcing
- [Decisions](decisions.md) — staff-curated video, duplicate source ranking
- [MVP scope](mvp-scope.md) — v1.6 YouTube linking
- [Video providers](../architecture/video-providers.md) — `VideoProvider` contract
- [ADR 001: YouTube first](../architecture/decisions/001-youtube-first.md)
