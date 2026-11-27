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
php artisan ai:prospect-audit https://example.com --type=business
```

## Still open (#32)

- [ ] `AiProspect` model + migration (workspace isolation)
- [ ] Filament list (admin only)
- [ ] Persist suppression table
- [ ] Host-app ICP config for signal weights

## Safe storage/import slice on dev (2026-11-27)

Additional workspace-scoped `Actions/Prospecting/*`, `AiProspect`/`AiProspectSuppression` models, additive migrations and dry-run-first `ai:prospects:import` provide a persistent, auditable lead list without harvesting contacts. See [detailed BMAD implementation notes](prompts/clients.md) and [issue #32](https://github.com/laraxot/module_ai_fila5/issues/32).

**Security:** The original `DetectWebsiteSignalsAction::execute()` remote HTTP path now fails closed; it previously lacked complete DNS rebinding, redirect and private-address controls. Its `analyzeHtml()` offline method remains supported. The older `DraftProspectOutreachAction` also explicitly sets `send_permitted=false`. The new draft action is workspace-scoped and never returns a recipient. No sending service or auto-crawler has been enabled.

**Outstanding:** host authorization/policies, Filament UI, provider selection, integration Pest/PHPStan, privacy review, safe network adapter and ICP validation.
