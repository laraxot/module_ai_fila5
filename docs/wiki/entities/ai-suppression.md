---
title: "AiSuppression Entity"
type: entity
module: AI
tags: [ai, suppression, do-not-contact, gdpr, persistent]
created: 2026-11-27
updated: 2026-11-27
qmd: "ai suppression do not contact persistent across re-import"
related:
  - ./ai-prospect-pipeline.md
  - ../entities/ai-prospect.md
  - ../entities/ai-prospect-draft.md
  - ../../../../docs/github/01-issues.md
---

# AiSuppression Entity

**Namespace:** `Modules\AI\Models\AiSuppression`

## Descrizione

Lista di soppressione (do-not-contact) persistente per identità deterministiche.
Rispettata anche nei re-import: la suppression sopravvive alle future
importazioni CSV/OSM per la stessa identità deterministica (`public_id`).

## Motivi (AiSuppressionReasonEnum)

| Motivo | Descrizione | Hard bounce? | User request? |
|---|---|---|---|
| `do_not_contact` | Richiesta esplicita non essere contattati | No | Sì |
| `unsubscribed` | Unsubscribed da mailing list | No | Sì |
| `bounced` | Email non consegnata (hard bounce) | Sì | No |
| `invalid_email` | Email sintatticamente invalida | N/A | No |
| `gdpr_request` | Richiesta GDPR cancellazione | No | Sì |

## Campi

| Campo | Tipo | Descrizione |
|---|---|---|
| `public_id` | string (uuid) | Identità deterministica del prospect |
| `workspace_id` | string (64) | Isolation per tenant |
| `email` | string | Email se nota |
| `reason` | string | Uno di `AiSuppressionReasonEnum` |
| `note` | longtext | Note manuali |
| `suppressed_by_user_id` | string | UUID operatore |
| `suppressed_at` | datetime | Quando |

## Uso in ImportProspectsAction

```php
// In ImportProspectsAction::execute()
$existing = AiProspect::query()->where('public_id', $publicId)->first();

if ($existing !== null && $this->isSuppressed($existing)) {
    continue; // skip suppressed prospect
}

// isSuppressed controlla:
AiSuppression::findForPublicId($publicId, $workspaceId) !== null;
```

## Query helper

```php
// Verifica se un prospect è soppresso
AiSuppression::findForPublicId('prospect_abc123', 'workspace_1'); // returns model or null
```