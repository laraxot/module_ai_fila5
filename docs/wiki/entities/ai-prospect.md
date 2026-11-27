---
title: "AiProspect Entity"
type: entity
module: AI
tags: [ai, prospect, entity, deterministic-identity, workspace-isolation]
created: 2026-11-27
updated: 2026-11-27
qmd: "ai prospect entity deterministic identity workspace isolation"
related:
  - ./ai-prospect-pipeline.md
  - ../entities/ai-prospect-signal.md
  - ../entities/ai-prospect-draft.md
  - ../entities/ai-suppression.md
  - ../../../../docs/github/01-issues.md
---

# AiProspect Entity

**Namespace:** `Modules\AI\Models\AiProspect`

**Extends:** `XotBaseModel` → custom `$connection = 'ai'`

## Descrizione

Prospect B2B per la pipeline di ricerca e acquisizione nuovi clienti.
Identità deterministica: `public_id` deriva da `sha256(domain)`, garantendo
univocità a prescindere dalla fonte di importazione (CSV, OSM, listing pubblico).

## Relazioni

- **signals** `HasMany<AiProspectSignal>` — prove e segnali per questa prospect
- **proposals** non direttamente collegata (usata via `AiActionProposal` con type='prospect')

## Stati (AiProspectStatusEnum)

| Stato | Transizione da | Descrizione |
|---|---|---|
| `new` | — | Prospect appena importato, in attesa di valutazione |
| `qualified` | `new` + confidence≥30 | Prospect qualificata dai segnali |
| `contacted` | `qualified` + score≥50 | Contattata via bozza approvata |
| `cold_lost` | `qualified` o `contacted` | Nessun follow-up positivo, soppressa o persa |
| `won` | `contacted` | Deal chiuso con successo |

## Colonne chiave

| Campo | Tipo | Descrizione |
|---|---|---|
| `public_id` | string (uuid format) | ID deterministico sha256(domain) |
| `workspace_id` | string (64) | Isolation per tenant/cliente |
| `status` | `AiProspectStatusEnum` | Ciclo di vita |
| `source` | string | csv | osm | public_listing | website_signals | email_verification |
| `source_url` | string | URL della sorgente dati |
| `source_at` | datetime | Timestamp raccolta dati |
| `raw_data` | json | Dati originali della riga di import |
| `summary` | longtext | Sintesi manuale o generata AI |
| `last_reviewed_at` | datetime | Ultima revisione umana |
| `last_contact_attempt_at` | datetime | Ultimo tentativo contatto |

## Esempi

```php
// ID deterministico dallo stesso dominio
AiProspect::deterministicId('acme.com'); // 'prospect_7f3b9c...'
AiProspect::deterministicId('ACME.COM'); // stesso ID per case-insensitive

// Query per workspace
AiProspect::query()->byWorkspace('ws_123')->get();
```