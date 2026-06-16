# Frontend Conventions

React + Inertia + Chakra UI standards for Main Event Archive.

## Readability & performance

- Keep components small; one concern per file
- Put state changes at the **lowest tier** that needs them — avoid re-rendering entire show page when toggling one match rating
- Split show page: `ShowHeader`, `MatchList`, `MatchRow`, `SpoilerToggle` — memoize `MatchRow` where props stable
- Prefer Inertia partial reloads / deferred props for heavy data when available
- No premature Zustand — Inertia props + React Context for spoiler state is enough until proven otherwise

## Semantic HTML

- One `<h1>` per page (show title)
- Match list: `<ol>` or `<ul>` with `<li>` per match
- Use `<article>` for show detail, `<nav>` for browse filters
- Buttons vs links: navigation = `<a>` / Inertia `<Link>`; actions = `<button>`

## Accessibility (WCAG 2.1 AA target)

- All images: meaningful `alt` or `alt=""` if decorative
- Spoiler toggle: accessible name ("Show spoilers for this show"), `aria-pressed` state
- Rating input: keyboard operable, label associated
- Focus visible on all interactive elements (Chakra default + verify)
- External links: indicate opens new tab for screen readers when using `target="_blank"`
- Color contrast: verify theme tokens against WCAG

## Spoiler UX

- Global header: optional default spoiler preference (logged-in users)
- Show page: prominent **per-show** "Show spoilers" control — user must opt in
- Visual distinction when spoilers on (banner or badge)
- Never render hard-spoiler fields in DOM when off — rely on server omitting props, not CSS `visibility:hidden`

## Inclusive language

- Neutral, welcoming copy in UI strings
- Avoid assumptions about fan knowledge in error messages
- See [vision.md](../product/vision.md)

## Key components

| Component | Notes |
|-----------|-------|
| `SpoilerToggle` | Per-show; sync with server preference |
| `MatchRow` | Spoiler-aware props only |
| `RatingStars` | MEA ratings only |
| `CagematchBadge` | Link-out, no score |
| `VideoPlaceholder` | “Where to watch” section; branded YouTube/Netflix link-out buttons |
| `YouTubeWatchButton` | Official white full logo on black (`youtube-logo-full-white.png`) |
| `NetflixWatchButton` | Official wordmark on black (`Netflix_Logo_RGB.png`) |

## SEO

- Meta description / OG tags: **card-only** when spoilers off (no winners, durations)
- When spoilers on for crawler: still default off for anonymous crawlers — use card-only meta

## Chakra theming

- Derive primary palette from logo in `docs/design/brand/`
- Document dark/light mode choice in theme when decided
- Use Chakra tokens, not hard-coded hex in components

## Related docs

- [Spoiler rules](../domain/spoiler-rules.md)
- [Stack](stack.md)
- [Design brief](../design/ux-design-brief.md)
