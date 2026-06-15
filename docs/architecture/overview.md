# Architecture Overview

Main Event Archive uses **orthogonal architecture**: domain logic at the center, external systems behind contracts/adapters.

## Layers

```
┌─────────────────────────────────────────────────────────┐
│  Public UI (Inertia + React + Chakra)                   │
│  Admin UI (Filament)                                    │
├─────────────────────────────────────────────────────────┤
│  HTTP (Controllers, Form Requests, Policies)            │
├─────────────────────────────────────────────────────────┤
│  Application (Actions, SpoilerContext, DTOs)            │
├─────────────────────────────────────────────────────────┤
│  Domain (Eloquent Models, Enums)                        │
├─────────────────────────────────────────────────────────┤
│  Adapters (VideoProvider, ShowDataImporter, Enricher)   │
├─────────────────────────────────────────────────────────┤
│  Infrastructure (PostgreSQL, Redis, Queue, HTTP)        │
└─────────────────────────────────────────────────────────┘
```

## Directory layout (target)

```
app/
├── Actions/              # Single-purpose invokable classes
├── Contracts/
│   ├── VideoProvider.php
│   ├── ShowDataImporter.php
│   └── ShowEnricher.php
├── Data/                 # DTOs (ImportRequest, VideoReference, etc.)
├── Enums/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Resources/        # Spoiler-aware API/Inertia transformers
├── Models/
├── Services/
│   └── SpoilerContext.php
└── Providers/
    └── VideoServiceProvider.php  # bind contracts

app/Filament/             # Admin resources (staff)

resources/js/             # Inertia React app (Chakra)
```

## Key boundaries

| Boundary | Contract | v1 implementation |
|----------|----------|-------------------|
| Video hosts | `VideoProvider` | `YouTubeProvider` |
| Catalog seed | `ShowDataImporter` | `WikidataShowImporter`, `WikipediaShowImporter` |
| AI assist | `ShowEnricher` | Deferred v1.6 |
| Spoilers | `SpoilerContext` | Server-side field filtering |

## Dependency rules

- Controllers → Actions/Services → Models
- Depend on **contracts** at adapter boundaries, not concrete vendors
- Bind implementations in service providers
- No `app()` / `resolve()` inside domain classes — constructor injection

## Spoiler-safe responses

All show/match serialization for public routes passes through resources that consult `SpoilerContext`. See [spoiler-rules.md](../domain/spoiler-rules.md).

## Caching

Redis for browse listings, rating aggregates, embeddability flags (v1.6+). See [caching.md](caching.md).

## Testing strategy

- Feature tests: routes, spoiler leakage, auth gates
- Unit tests: importers, video provider URL parsing, SpoilerContext
- Use factories; run via `vendor/bin/sail artisan test`

## Development environment

- **Always** use Sail: `vendor/bin/sail` for PHP, Artisan, Composer, npm
- PostgreSQL + Redis (see `.env.example`)
- Do not run PHP/Composer on host — version mismatch risk

## ADRs

Architecture Decision Records in [decisions/](decisions/).

## Related docs

- [Video providers](video-providers.md)
- [Data model](../domain/data-model.md)
- [MVP scope](../product/mvp-scope.md)
