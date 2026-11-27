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
