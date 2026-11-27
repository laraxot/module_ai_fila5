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
| prospector audit/rank/draft | PROFILE signals | `DetectWebsiteSignalsAction` + `WebsiteSignalCatalog` |
| researcher brief | why they'd buy | `BuildProspectBriefAction` |
| stop / suppression | outbox approval | `ApplyProspectSuppressionAction` + `requires_approval` |
| pipeline board | discover→verify | `NormalizeProspectImportAction` + rank |

AGPL Sellbird is **not** vendored. Patterns only.

## Website signals (deepened 2026-11-27)

Contract: [docs/contracts/website-signals.md](contracts/website-signals.md).

- **Offline-first:** `analyzeHtml` / `analyzeHtmlResult` — host supplies HTML after a safe fetch.
- **Fail-closed:** `execute($url)` does not open network sockets (SSRF).
- **Polarity:** `gap` (rank score) vs `strength` (score 0, brief only).
- **Catalog:** mobile, SEO (title/meta/canonical/OG/H1/lang/JSON-LD), performance, HTTPS hint, trust, booking, chat, forms, analytics, social, phone, copyright, commerce ICP, hospitality ICP (`no-menu` / `no-hours` / `no-order-online`).
- **Weights:** `config('ai.prospect.signals.weights')` overrides `WebsiteSignalCatalog::defaults()`.

## ECC non-negotiables

1. Public HTML only; no private/login scrape in this action
2. No invented personal emails
3. No automatic send — drafts only
4. Signals = hints unless `confidence=measured|error`
5. Suppression honored on re-import / draft

## CLI

```bash
php artisan ai:prospect-audit https://example.com --type=business  # fails closed until safe HTTP adapter
php artisan ai:prospects:import demo tests/Fixtures/prospects-demo.csv  # dry-run when present
```

## Status (#32)

- [x] Deep offline signal catalog + Pest
- [x] Configurable weights
- [ ] SSRF-safe fetch adapter (host or reviewed module service)
- [ ] Filament review UI, durable consent, provenance (#36–#41)
