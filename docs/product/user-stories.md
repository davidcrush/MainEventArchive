# User Stories

Format: **As a** [role], **I want** [goal], **so that** [benefit].

## Fan (guest)

- As a guest, I want to browse WCW PPVs by year, so that I can explore the catalog without an account.
- As a guest, I want to search for shows by name or wrestler on the card, so that I can find specific events.
- As a guest, I want spoilers off by default, so that I can read the card without seeing results.
- As a guest, I want to enable spoilers on a specific show when I'm ready, so that I control what I see.
- As a guest, I want to understand that video isn't available yet, so that I'm not confused by empty player areas.

## Fan (registered)

- As a registered user, I want to rate shows and matches (1–5 stars), so that I can share my opinion with the community.
- As a registered user, I want to add shows to my watchlist, so that I can track what to watch when video is linked.
- As a registered user, I want to mark shows as watched, so that I can filter what I've already seen.
- As a registered user, I want my spoiler preferences remembered, so that I don't reset them every visit.

## Fan (video — v1.6+)

- As a fan, I want to watch a full show via YouTube embed or external link, so that I don't leave the site unnecessarily.
- As a fan, I want to jump to a specific match when spoilers are on, so that I can watch one match quickly.
- As a fan, I want a fallback link when embed is blocked, so that I can still watch on YouTube.

## Fan (v1.7+)

- As a fan, I want to watch individual matches when the full show isn't on YouTube, so that partial catalog coverage still helps me.

## Staff / admin

- As staff, I want to review imported shows before publish, so that card data is accurate.
- As staff, I want to flag surprise matches and entrants, so that spoiler mode hides them when possible.
- As staff, I want to paste a Cagematch URL without importing their ratings, so that fans can cross-reference.
- As staff, I want to link YouTube URLs at show level (v1.6), so that fans can watch.
- As staff, I want AI-suggested timestamps reviewed before publish (v1.6), so that bad data doesn't go live.

## Agent / developer

- As an agent, I want clear docs on spoiler fields and schema, so that I don't leak results in API responses.
- As an agent, I want to ask when specs are ambiguous, so that I don't hallucinate requirements.

## Related docs

- [MVP scope](mvp-scope.md)
- [Spoiler rules](../domain/spoiler-rules.md)
- [Admin workflow](../domain/admin-workflow.md)
