---
title: "AI Module - Roadmap"
type: documentation
tags: [roadmap, planning, module]
updated: 2026-09-17
---

# AI Module - Roadmap

> Merged from `ROADMAP.md`, `roadmap-and-issues.md` and `roadmap/README.md` on 2026-09-17 (docs cleanup pass, story `docs-improvement-pass-2026-09-17`). The three source files described the same topic with different, sometimes conflicting, snapshots; this is now the single canonical roadmap doc for `Modules/AI`. `product-roadmap.md`/`product_roadmap.md`/`PRODUCT_ROADMAP.md` (unfilled generic template, "Owner: Product Team") were deleted as duplicates with no unique content — they pointed here.

## Mission

Provide AI-powered features (completion, sentiment, fine-tuning, predictions support) as small, queueable Actions in `app/Actions/`, aligned with Filament v4 and Laravel 12 (see `laravel/CLAUDE.md` for current framework versions — an older archived snapshot of this file said "Laravel 13", which does not match the current `laravel/framework` constraint and was dropped as stale).

## Actions that exist today (verified 2026-09-17 against `app/Actions/*.php`)

| Action | Purpose |
|---|---|
| `CompletionAction.php` | Text completion via the configured AI provider |
| `SentimentAction.php` | Sentiment analysis |
| `ClassifyTicketAction.php` | Ticket/content classification |
| `SuggestSolutionsAction.php` | Suggests solutions/responses |
| `GeneratePredictionsAction.php` | Generates predictions for the Predict module (see `generate-predictions-action.md`) |
| `PredictionDraftFallbackTemplatesAction.php` | Deterministic fallback templates when a provider is unavailable (see `predict-drafts-contract.md`) |
| `ContextCompressorAction.php` | Prompt/context compression (see `wiki/concepts/openrouter-context-compression.md`) |
| `AiJsonResponseDecoderAction.php` | Decodes structured JSON responses from a model |
| `CreateAiActionProposalAction.php`, `ConfirmAiActionProposalAction.php`, `CancelAiActionProposalAction.php` | Human-in-the-loop proposal/confirm/cancel workflow for AI-suggested actions |

This list is materially larger than the "Completion API + Sentiment Analysis only, 60% complete" snapshot recorded in October 2025 (below) — later Actions (predictions, proposal workflow, context compression) were added afterward without a roadmap update. Treat completion percentages anywhere in this file as historical, not current, status.

## October 2025 snapshot (historical, from the former `roadmap-and-issues.md`)

**Status at the time**: PHPStan 0 errors (Level 9) after fixing `navigationIcon` usage in `Completion.php`/`Dashboard.php` for XotBasePage compliance.

| Feature | Status (Oct 2025) | Note |
|---|---|---|
| Completion API | 90% | OpenAI integration |
| Sentiment Analysis | 80% | Base implemented |
| Fine-Tuning | 40% | Partial |
| Auto-Categorization | 0% | Not implemented (still not implemented as of 2026-09-17) |
| Chatbot | 0% | Not implemented (still not implemented as of 2026-09-17) |

Planned features not yet built as of this cleanup pass:
- **Auto-Categorization Tickets** — classify tickets automatically via `AutoCategorizeTicketAction` (proposed, not present in `app/Actions/`).
- **Smart Duplicate Detection** — embedding-based similarity search for duplicate tickets (proposed, not present).
- **Priority Prediction** — ML-based priority scoring from ticket content.
- **Chatbot** — conversational assistant for FAQ/status checks.
- **Image Recognition** — analyze photos attached to reports/tickets.

Note: the original October 2025 draft illustrated these proposals with a "FixCity" ticketing example (`Ticket`, citizen reports). The `AI` module is shared across several Laraxot bases (`laraxot/module_ai_fila5`, see `composer.json`); FixCity was one of those bases. In this base (`base_restaurant_fila5`) there is no `Ticket` model — read the FixCity-flavoured examples as illustrative patterns to adapt, not as claims about this base's domain.

## Known open technical items (from the October 2025 snapshot, unverified as still-open)

- **API calls not cached** — wrap provider calls in `Cache::remember()`.
- **No rate limiting** — add a named `RateLimiter` for AI endpoints.

These two were flagged as open in October 2025; re-verify against current code before acting on them, this pass did not re-audit caching/rate-limiting.

## Vision (from the former `roadmap/README.md`)

Longer-term direction for the module, independent of the FixCity-flavoured backlog above:
- AI model integration (loading, inference, versioning, monitoring)
- Natural language processing (text/sentiment analysis)
- Image recognition
- Predictive analytics
- Automation tools
- AI-powered recommendations

## Related docs

- [PRD](prd.md)
- [Product strategy](product-strategy.md) / [strategy](strategy.md)
- [PHPStan status](phpstan-status.md)
- [Predict integration overview](predict-generation.md), [predict-drafts-contract.md](predict-drafts-contract.md), [generate-predictions-action.md](generate-predictions-action.md)
