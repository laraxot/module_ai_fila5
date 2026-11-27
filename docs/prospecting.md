---
title: "B2B prospecting (agnostic)"
module: AI
tags: [bmad, ecc, prospecting, closer-skill, sellbird]
issue: 32
updated: 2026-11-27
---

# Prospecting engine (issue #32)

## Study map

| Closer-skill | Sellbird | Module AI |
|--------------|----------|-----------|
| prospector audit/rank/draft | PROFILE signals | `DetectWebsiteSignalsAction` |
| researcher brief | why they'd buy | `BuildProspectBriefAction` |
| stop / suppression | outbox approval | `ApplyProspectSuppressionAction` + `requires_approval` |
| pipeline board | discover→verify | `NormalizeProspectImportAction` + rank |

AGPL Sellbird is **not** vendored. Patterns only.

## ECC non-negotiables

1. Public HTML only; block private/localhost hosts
2. No invented personal emails
3. No automatic send — drafts only
4. Signals = hints unless `confidence=measured|error`
5. Suppression honored on re-import / draft

## CLI

```bash
php artisan ai:prospect-audit https://example.com --type=business  # fails closed until safe HTTP adapter
php artisan ai:prospects:import demo tests/Fixtures/prospects-demo.csv  # dry-run; no writes
```

## Status and outstanding work (#32)

- [x] `AiProspect` model and additive migration landed on `dev` (runtime host integration **not verified**).
- [x] `AiProspectSuppression` tombstone model and migration landed (concurrency and tenant checks still pending).
- [x] Configurable signal weights and workspace-scoped Action slice landed; pilot filters currently include restaurant-specific OSM categories and must become host-configured.
- [ ] Filament list/detail and permission policies for scoped operator review (#38).
- [ ] Durable consent per purpose/channel and legal review (#36).
- [ ] Provenance, stable provider identity, multi-site dedupe, normalized observations (#40, #39).
- [ ] Single documented prospecting contract: reconcile `Actions/Prospect` vs `Actions/Prospecting` (#40).
- [ ] Host ICP validation and ranked evidence quality (#37).
- [ ] Laravel host PHPStan/Pest/migrations and security threat gate (#33, #41).

## Safe storage/import slice on dev (2026-11-27)

Additional workspace-scoped `Actions/Prospecting/*`, `AiProspect`/`AiProspectSuppression` models, additive migrations and dry-run-first `ai:prospects:import` provide a persistent, auditable lead list without harvesting contacts. See [detailed BMAD implementation notes](prompts/clients.md) and [issue #32](https://github.com/laraxot/module_ai_fila5/issues/32).

**Security:** The original `DetectWebsiteSignalsAction::execute()` remote HTTP path now fails closed; it previously lacked complete DNS rebinding, redirect and private-address controls. Its `analyzeHtml()` offline method remains supported. The older `DraftProspectOutreachAction` also explicitly sets `send_permitted=false`. The new draft action is workspace-scoped and never returns a recipient. No sending service or auto-crawler has been enabled.

**Outstanding:** host authorization/policies, Filament UI, provider selection, integration Pest/PHPStan, privacy review, safe network adapter and ICP validation.
