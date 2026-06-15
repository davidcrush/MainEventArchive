# ADR 001: YouTube First for Video Providers

## Status

Accepted

## Context

Main Event Archive links to external video; we need a provider abstraction. YouTube has the largest back catalog of full shows and match uploads for historical WCW/WWE content.

## Decision

- Implement `VideoProvider` contract with `YouTubeProvider` as first adapter
- Store `provider = youtube` and `external_id` on `Video` model
- Embed when embeddable; external link fallback
- Defer Vimeo/Dailymotion until demand exists

## Consequences

- v1.6 work focuses on YouTube URL parsing, embed, and timestamp jump
- Schema supports other `provider` string values without migration
- Per-match videos (v1.7+) use same contract on `match_id` videos

## Related

- [video-providers.md](../video-providers.md)
