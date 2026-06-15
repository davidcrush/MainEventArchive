# Development Environment

Main Event Archive uses **Laravel Sail** with **PostgreSQL** and **Redis**. Do not use SQLite when developing with Sail.

## Setup

```bash
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
```

## .env defaults (Sail)

| Variable | Value | Notes |
|----------|-------|-------|
| `DB_CONNECTION` | `pgsql` | PostgreSQL container |
| `DB_HOST` | `pgsql` | Sail service name |
| `DB_DATABASE` | `laravel` | Matches compose.yaml |
| `DB_USERNAME` | `sail` | |
| `DB_PASSWORD` | `password` | |
| `CACHE_STORE` | `redis` | |
| `QUEUE_CONNECTION` | `redis` | |
| `REDIS_HOST` | `redis` | Sail service name |

## Commands

Always prefix with `vendor/bin/sail`:

```bash
vendor/bin/sail artisan migrate
vendor/bin/sail artisan test --compact
vendor/bin/sail composer install
vendor/bin/sail npm run dev
```

## Testing database

Sail creates a PostgreSQL testing database via `vendor/laravel/sail/database/pgsql/create-testing-database.sql`. PHPUnit uses `DB_DATABASE=testing` from `phpunit.xml`.

## Host machine

Do not run `php`, `composer`, or `artisan` directly on the host — versions may not match the Sail container (PHP 8.5).

## Production

VPS deployment via Laravel Forge. Use PostgreSQL and Redis in production; align env vars with Forge provisioning.

## Related

- [compose.yaml](../compose.yaml)
- [Architecture overview](architecture/overview.md)
