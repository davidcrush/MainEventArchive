# Product Vision

## What is Main Event Archive?

Main Event Archive (MEA) is a pro wrestling database focused on **shows with available video**. We help fans find content they want to watch — we do **not** host video. We index shows, link to third-party providers (YouTube first), and provide spoiler-safe browsing, community ratings, and personal tracking.

## Core principles

### Catalog before video

We build catalog infrastructure first (promotions, show types, brands, import pipeline, spoiler rules) before investing heavily in video linking. v1–v1.5 is a spoiler-safe card archive; video playback arrives at v1.6. This avoids painting ourselves into a corner when expanding to WWE, TV volume, and brand splits.

### No self-hosting

Video stays on YouTube (and future providers). We embed when allowed or link externally. We never store or serve video files.

### Spoiler-conscious by default

Users browse **cards**, not results, unless they opt in per show. Match results, lengths, and timestamps are hidden by default. We do our best to hide surprise matches and entrants when staff flag them.

### Ethical data sourcing

- Initial seed via legitimate APIs (Wikidata, Wikipedia MediaWiki API)
- No scraping proprietary databases (Cagematch, ProFightDB, etc.)
- Third-party ratings: link-out badges only — never display or cache their scores
- Attribute Wikipedia/Wikidata on `/attribution`

### Orthogonal architecture

Use contracts and adapters at boundaries (video providers, data importers, enrichers). Low coupling, high cohesion. Small classes and functions.

### Welcoming community

Inclusive language and accessible design (WCAG 2.1 AA target). Semantic HTML, screen reader support, alt text. We welcome everyone; we do not tolerate intolerance.

### Ask, don't assume

Agents and contributors should ask when requirements are unclear. Present options with pros/cons and a recommendation. Pushback on unclear specs is welcome.

## Tech stack

| Layer | Choice |
|-------|--------|
| Backend | Laravel 13, PHP 8.5 |
| Database | PostgreSQL |
| Cache | Redis |
| Frontend | React, Inertia.js, Chakra UI |
| Dev | Laravel Sail (always run commands via `vendor/bin/sail`) |
| Production | VPS via Laravel Forge |

## What success looks like

1. **v1:** WCW PPVs (1993+) browsable with spoilers, search, ratings, watchlist — import pipeline proven
2. **v1.6:** YouTube linking live — fans can watch from show pages
3. **Long term:** Multi-promotion catalog with trustworthy metadata and a community that trusts our spoiler handling

## Related docs

- [Glossary](glossary.md)
- [MVP scope & roadmap](mvp-scope.md)
- [User stories](user-stories.md)
- [Decisions](decisions.md)
