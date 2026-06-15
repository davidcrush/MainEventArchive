# Design Assets

Design reference files for Main Event Archive — **not** production web assets served directly.

## Directory layout

```
design/
├── README.md           # This file
├── ux-design-brief.md  # Brief for design agents
├── mocks/              # UX mockups (PNG, JPG, PDF, Figma exports)
└── brand/              # Source logo files (JPG, SVG, etc.)
```

## Production assets

Optimized logos for the running app go in:

```
resources/images/brand/
resources/images/hero-ring.jpg
```

Import via Vite in React components. Export PNG or SVG from source files in `brand/`.

The public header uses `BrandLogo.tsx` (cropped mark + wordmark) sourced from `resources/images/brand/logo.jpg`.

## Mocks → pages mapping

| Mock file | Page |
|-----------|------|
| `mocks/mock-landing.jpg` | Home (header, hero, featured cards) |
| `mocks/mocks.jpg` | Full catalog UX reference (browse, show detail states) |
| `mocks/component-sheet.jpg` | Chakra component styling reference |
| `brand/logo.jpg` | Header logo mark + theme palette source |

**Note for agents:** Mock JPGs may not appear in Glob/search tools; read by explicit path (e.g. `docs/design/mocks/mock-landing.jpg`).

## For agents

- Use mocks for visual intent; **docs/domain** and **docs/product** override mocks if they conflict
- Do not commit huge binary files without team agreement; Figma links may go here as alternative

## Related

- [Frontend stack](../frontend/stack.md)
- [UX design brief](ux-design-brief.md)
