# Main Event Archive Documentation

Start here for product, domain, architecture, and frontend documentation.

## Quick links

| I want to… | Read |
|------------|------|
| Understand the product | [product/vision.md](product/vision.md) |
| See v1 scope | [product/mvp-scope.md](product/mvp-scope.md) |
| Learn domain terms | [product/glossary.md](product/glossary.md) |
| Understand schema | [domain/data-model.md](domain/data-model.md) |
| Implement spoilers | [domain/spoiler-rules.md](domain/spoiler-rules.md) |
| Build import pipeline | [domain/data-import.md](domain/data-import.md) |
| **Initial catalog seeding (local + Forge)** | [domain/initial-catalog-seeding.md](domain/initial-catalog-seeding.md) |
| Wikipedia result formats | [domain/wikipedia-parser.md](domain/wikipedia-parser.md) |
| Architecture & adapters | [architecture/overview.md](architecture/overview.md) |
| Frontend setup | [frontend/stack.md](frontend/stack.md) |
| UX mocks & logo | [design/README.md](design/README.md) |

## For AI agents

1. Read [AGENTS.md](../AGENTS.md) (Laravel Boost + MEA section)
2. Activate skill: `main-event-archive` for domain work
3. Use `docs/domain/data-model.md` as schema source of truth
4. **Ask when unsure** — see [product/decisions.md](product/decisions.md)

## Documentation map

```
docs/
├── product/          Vision, glossary, MVP, user stories, decisions
├── domain/           Data model, spoilers, import, admin, third-party
├── architecture/     Overview, video, caching, ADRs
├── frontend/         Stack, conventions
└── design/           Mocks, brand assets, UX brief
```

## Development

All commands via Sail:

```bash
vendor/bin/sail up -d
vendor/bin/sail artisan migrate
vendor/bin/sail npm run dev
```

PostgreSQL + Redis — see [.env.example](../.env.example) and [development-environment.md](development-environment.md).

## Implementation roadmap (summary)

v1 WCW PPVs 1993+ → catalog expansion v1.1–v1.5 → YouTube v1.6 → per-match video v1.7+

Full detail: [product/mvp-scope.md](product/mvp-scope.md)
