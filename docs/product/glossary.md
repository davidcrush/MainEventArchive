# Glossary

Terms used consistently across Main Event Archive documentation and code.

## Promotion

A wrestling company or federation (e.g. WCW, WWE). v1 uses a flat `Promotion → Show` model. WWE brand split (Raw/SmackDown) uses a nullable `brand` field on shows from v1.3 onward.

## Show

A single wrestling event — a PPV, TV episode, or special (e.g. Clash of the Champions). Has metadata (date, venue, promotion, type), a **card** of matches, optional video link(s), and provenance fields from import.

## Card

The ordered list of matches (and optionally segments) on a show. Default public view shows the card **without results** when spoilers are off.

## Match

One bout or segment on a card. Has participants, match type, card order, and optionally results, timestamps, and video. Rateable by users.

## Participant

A wrestler or team member on a match. v1 stores names on `match_participants`; full wrestler profiles are deferred.

## Hard spoiler

Must never appear when spoilers are off: winner, finish, duration, timestamps, title change flags. Server-enforced.

## Soft spoiler

Best-effort hide when staff flags: surprise matches, surprise entrants, tournament/bracket outcomes on the same show.

## Spoilers off / on

- **Off (default):** Card with participants only; no results, lengths, or jump-to-match
- **On (per-show opt-in):** Full results, durations, timestamps when video exists

## Watchlist

User-saved shows to watch later. Useful even before v1.6 video linking ("save for when video is available").

## Watched

User-marked shows they have seen.

## Video (MEA)

A link to external playback (YouTube first), attached to a **show** (full broadcast) or **match** (individual upload, v1.7+). Not hosted by MEA.

## Video provider

Adapter implementing `VideoProvider` — parses URLs, builds embed/external links, checks embeddability.

## Import / provenance

Shows seeded from Wikidata/Wikipedia carry `source`, `source_id`, `source_url`, and review status until staff publishes.

## MEA rating

Community star rating (1–5) from MEA users — the **only** numeric rating displayed on site.

## External reference

Outbound link to third-party sites (e.g. Cagematch badge). We do not display or cache their rating data.

## Catalog infrastructure

Schema, import pipeline, spoiler-safe APIs, admin curation, and browse/search — built in v1–v1.5 before video features.
