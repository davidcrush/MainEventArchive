# Frontend Stack

Planned public frontend for Main Event Archive. **Not yet installed** — current skeleton uses Blade + Tailwind.

## Target stack

| Package | Role |
|---------|------|
| React 19 | UI components |
| Inertia.js v2 | Laravel ↔ React without separate API |
| Chakra UI v3 | Component library + theming |
| Vite | Bundling (already in project) |
| TypeScript | Recommended for `resources/js` |

## Installation (when implementing)

Follow Laravel Breeze Inertia React starter, then add Chakra UI. All commands via Sail:

```bash
vendor/bin/sail composer require laravel/breeze --dev
vendor/bin/sail artisan breeze:install react --typescript
vendor/bin/sail npm install @chakra-ui/react @emotion/react
```

Exact versions: verify with `search-docs` at implementation time.

## App structure (target)

```
resources/js/
├── app.tsx                 # Inertia + Chakra provider
├── Pages/
│   ├── Home.tsx
│   ├── Browse/
│   ├── Search/
│   ├── Shows/Show.tsx      # Card + spoiler toggle
│   └── Watchlist/
├── Components/
│   ├── Layout/
│   ├── ShowCard.tsx
│   ├── MatchRow.tsx
│   ├── SpoilerToggle.tsx
│   ├── RatingStars.tsx
│   ├── CagematchBadge.tsx
│   └── VideoPlaceholder.tsx
├── hooks/
│   └── useSpoilers.ts
└── theme/
    └── index.ts            # Chakra theme from logo
```

## Admin vs public

| Surface | Stack |
|---------|-------|
| Public site | Inertia + React + Chakra |
| Staff admin | Filament (Blade/Livewire) |

## Design assets

Reference mocks and source logo: [../design/README.md](../design/README.md)

Production logo: `resources/images/brand/` (optimized PNG/SVG via Vite)

## Related docs

- [Conventions](conventions.md)
- [UX design brief](../design/ux-design-brief.md)
- [MVP scope](../product/mvp-scope.md)
