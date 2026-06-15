# Caching Strategy

Redis caching plan for Main Event Archive.

## Principles

- Cache read-heavy, relatively stable data
- Invalidate on write (model observers or explicit flush)
- Short TTLs for data that changes often
- PostgreSQL remains source of truth

## Dev / production

- `CACHE_STORE=redis` (see `.env.example`)
- Redis service via Sail in development
- Forge: Redis on VPS for production

## Cache keys (v1)

| Key pattern | Content | TTL | Invalidate when |
|-------------|---------|-----|-----------------|
| `browse:{promotion}:{year}:{page}` | Paginated show list DTO | 15 min | Show published/updated |
| `show:{id}:rating` | Average stars + count | 5 min | Rating created/updated |
| `match:{id}:rating` | Average stars + count | 5 min | Rating created/updated |
| `search:{hash}` | Search results page | 10 min | Any show in result set updated |

Use cache tags if supported by driver configuration for bulk invalidation by promotion.

## Cache keys (v1.6+)

| Key pattern | Content | TTL | Invalidate when |
|-------------|---------|-----|-----------------|
| `video:{id}:embeddable` | Boolean + reason | 24 hours | Nightly verify job or manual relink |
| `show:{id}:has_video` | Computed playback availability | 1 hour | Video linked/removed |

## What not to cache (v1)

- Per-user watchlist/watched (user-specific; query DB with indexes)
- Spoiler-filtered show payloads keyed without spoiler state — **always include spoiler context in cache key or don't cache personalized responses**

## Spoiler + cache

Public show pages with spoiler variants:

- Option A: Do not cache full show Inertia props (simplest v1)
- Option B: Cache base show data; apply spoiler filter after cache hit

**Default for v1:** cache browse/search lists only; build show page per request with SpoilerContext.

## Session

Consider `SESSION_DRIVER=redis` in production for horizontal scaling — optional for v1 single server.

## Related docs

- [Architecture overview](overview.md)
- [Overview](../README.md)
