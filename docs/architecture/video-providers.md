# Video Providers

Contract and YouTube adapter specification for external video linking (v1.6+).

## Design goals

- YouTube first; swappable providers
- Videos attach to **Show** (full broadcast) or **Match** (individual upload, v1.7+)
- Embed when allowed; external link fallback
- No video hosting on MEA

## Video model

See [data-model.md](../domain/data-model.md). Exactly one of `show_id` or `match_id` per `Video` row.

## Contract

```php
interface VideoProvider
{
    public function parseUrl(string $url): VideoReference;

    public function embedUrl(VideoReference $ref, ?int $startSeconds = null): string;

    public function externalUrl(VideoReference $ref, ?int $startSeconds = null): string;

    public function isEmbeddable(VideoReference $ref): bool;
}
```

```php
readonly class VideoReference
{
    public function __construct(
        public string $provider,
        public string $externalId,
        public ?int $durationSeconds = null,
    ) {}
}
```

## YouTube adapter (v1.6)

### URL parsing

Support common formats:

- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- Optional `t=` or `start=` for deep links (admin entry)

### Embed URL

```
https://www.youtube.com/embed/{VIDEO_ID}?start={seconds}
```

### External URL

```
https://www.youtube.com/watch?v={VIDEO_ID}&t={seconds}
```

### Embeddability

- Check on save when staff links URL (oEmbed or YouTube API if available)
- Background job re-verify nightly (v1.6+) — restrictions change
- Store `embeddable`, `embed_disabled_reason`, `last_verified_at`

### UI behavior

| embeddable | UX |
|------------|-----|
| true | Inline embed + optional jump-to-match |
| false | Prominent "Watch on YouTube" external link |

## Playback resolution (v1.7+)

```php
interface PlaybackResolver
{
    public function resolve(Show $show, ?Match $match = null): PlaybackTarget;
}
```

Priority documented in [data-model.md](../domain/data-model.md).

## Multiple sources

`VideoSource` rows with `rank` and `status` (active, unavailable, embed_disabled). UI uses primary active source.

## Future providers

Vimeo, Dailymotion, archive.org — implement new `VideoProvider` + bind in config. No changes to show/match UI contracts.

## Related docs

- [ADR 001](decisions/001-youtube-first.md)
- [AI enrichment](../domain/ai-enrichment.md)
- [Caching](caching.md) — embeddability cache
