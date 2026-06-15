# ADR 003: AI Enrichment as v1.6 Stretch

## Status

Accepted (deferred implementation)

## Context

Match timestamps and spoiler flagging are labor-intensive. AI could suggest values but risks hallucination and YouTube ToS issues.

## Decision

- Defer all YouTube linking and AI enrichment to **v1.6**
- v1–v1.5: catalog only; nullable video/timestamp schema ready
- v1.6: staff YouTube linking + layered timestamp suggestions (chapters → transcript → LLM)
- AI output always requires staff review; never auto-publish
- `ShowEnricher` contract mirrors importer pattern

## Consequences

- No AI or video code in v1 reduces scope
- Admin UI for enrichment review built at v1.6
- Spoiler AI assist may share same review queue as timestamps

## Related

- [ai-enrichment.md](../../domain/ai-enrichment.md)
