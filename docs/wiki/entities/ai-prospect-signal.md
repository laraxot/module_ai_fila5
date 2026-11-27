---
title: "AiProspectSignal Entity"
type: entity
module: AI
tags: [ai, prospect, signal, evidence, provenance]
created: 2026-11-27
updated: 2026-11-27
qmd: "ai prospect signal deterministic provenance quality confidence score breakdown"
related:
  - ./ai-prospect-pipeline.md
  - ../entities/ai-prospect-draft.md
  - ../entities/ai-suppression.md
  - ../../../../docs/github/01-issues.md
---

# AiProspectSignal Entity

**Namespace:** `Modules\AI\Models\AiProspectSignal`

## Descrizione

Prove puntuali per una prospect, generate automaticamente da `ComputeProspectSignalsAction`.
Nessuna deduzione da pagine non esaminate — ogni prova è puntuale.

## Campi

| Campo | Tipo | Descrizione |
|---|---|---|
| `source` | string (csv, osm, public_listing, website_signals, email_verification) | Fonte della raccolta dati |
| `source_url` | string | URL del business/OSM pagina esaminata |
| `source_at` | datetime | Timestamp della raccolta dati |
| `quality_source` | string (high|medium|low) | Qualità fonte (verificabile) |
| `confidence` | integer (0-100) | Confidenza nella fonte |
| `score` | integer (0-100) | Score spiegabile per ICP fit |
| `breakdown` | array<string,mixed> | Dettaglio per campo (value, score, reason) |
| `note` | longtext | Nota manuale |

## Esempi

```php
new AiProspectSignal([
    'ai_prospect_id' => 'prospect_abc',
    'source' => 'osm',
    'source_url' => 'https://www.example.com',
    'source_at' => now(),
    'quality_source' => 'high',
    'confidence' => 85,
    'score' => 72,
    'breakdown' => [
        'industry' => ['value' => 'Manufacturing', 'score' => 80, 'reason' => 'matches target'],
    ],
]);
```