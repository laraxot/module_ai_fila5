---
title: "Contratto — CancelAiActionProposalAction"
type: contract
action: "Modules\\AI\\Actions\\CancelAiActionProposalAction"
module: AI
status: active
tags: [contract, action, ai, proposta, human-in-the-loop]
created: 2026-11-26
updated: 2026-11-26
related:
  - ../adr/0001-ai-proposes-human-confirms.md
  - create-ai-action-proposal-action.md
  - confirm-ai-action-proposal-action.md
---

# CancelAiActionProposalAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/CancelAiActionProposalAction.php`. Una persona rifiuta ciò che l'AI proponeva: la proposta passa a
`cancelled` e non viene eseguita ([ADR](../adr/0001-ai-proposes-human-confirms.md)). Chiamante di produzione: la tabella delle
proposte (`Filament/Resources/AiActionProposalResource/Tables/AiActionProposalsTable.php`).

## Input

- `execute(AiActionProposal $proposal)`.

## Output

- La stessa proposta, con `status` = `cancelled`.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-CANCEL-INV-1 | La proposta finisce `cancelled` e viene salvata. | — (non dimostrato) |
| AI-CANCEL-INV-2 | Nessun handler gira e `result`, `error`, `executed_at` non vengono toccati: annullare non esegue nulla. | — (non dimostrato) |

## Effetti collaterali

- Un `UPDATE` di `ai_action_proposals` (solo `status`).
- Nessuna notifica, nessun log.

## Errori

- Errori di database sul salvataggio risalgono al chiamante.

## Non fa

- Non controlla lo stato di partenza.
- Non registra chi ha annullato né quando: non esiste `cancelled_by` né `cancelled_at`.
- Non annulla gli effetti di una proposta già eseguita.

## Limiti noti (difetti da correggere, non da imitare)

- Una proposta `executed` si può marcare `cancelled`: l'archivio direbbe che non è stata eseguita quando invece lo è stata. Al
  contrario una `cancelled` si può ancora confermare (`ConfirmAiActionProposalAction` non guarda lo stato).
- Nessun test esercita questa Action.
