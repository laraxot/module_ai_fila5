---
title: "AiProspectDraft Entity"
type: entity
module: AI
tags: [ai, prospect, draft, contact, lawful-basis, human-review]
created: 2026-11-27
updated: 2026-11-27
qmd: "ai prospect draft contact lawful basis human review no auto-send"
related:
  - ./ai-prospect-pipeline.md
  - ../entities/ai-prospect.md
  - ../entities/ai-prospect-signal.md
  - ../entities/ai-suppression.md
  - ../../../../docs/github/01-issues.md
---

# AiProspectDraft Entity

**Namespace:** `Modules\AI\Models\AiProspectDraft`

## Descrizione

Brief e bozza di primo contatto per una prospect.
**MAI invio automatico**: approvazione umana obbligatoria per ogni messaggio.

## Stati (AiProspectDraftStatusEnum)

| Stato | Transizione da | Descrizione |
|---|---|---|
| `draft` | — | Bozza generata automaticamente, in attesa revisione |
| `pending_review` | `draft` | Inoltrata per revisione umana |
| `reviewed` | `pending_review` | Revisionata, lawful_basis verificato |
| `sent` | `reviewed` | Inviata dopo approvazione umana |
| `suppressed` | qualsiasi | Soppressa (do-not-contact) |

## Campi chiave

| Campo | Tipo | Descrizione |
|---|---|---|
| `subject` | string | Oggetto email |
| `body` | longtext | Corpo messaggio |
| `channel` | string | email, linkedin, etc. |
| `status` | `AiProspectDraftStatusEnum` | Stato bozza |
| `lawful_basis` | json | `{basis: "legitimate_interest", "evidence": "..."}` **obbligatorio** |
| `personalization` | json | `{icebreaker, hooks, company_data}` per merge tags |
| `reviewed_by_user_id` | string | UUID revisore umano |
| `reviewed_at` | datetime | Timestamp revisione |
| `sent_at` | datetime | Timestamp invio (solo dopo approved) |
| `suppression_reason` | string | Se suppressed, motivo |

## Esempi

```php
$draft = new AiProspectDraft([
    'ai_prospect_id' => 42,
    'subject' => 'Introduzione a Acme Srl',
    'body' => "Ciao Acme Srl,\n\nHo visto...",
    'channel' => 'email',
    'status' => AiProspectDraftStatusEnum::DRAFT,
    'lawful_basis' => [
        'basis' => 'legitimate_interest',
        'evidence' => 'Prospect found via public business data',
    ],
    'personalization' => [
        'icebreaker' => 'Complimenti per l\'espansione 2024',
    ],
]);
```