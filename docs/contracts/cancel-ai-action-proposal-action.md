---
title: "Contratto — CancelAiActionProposalAction"
type: contract
action: "Modules\\AI\\Actions\\CancelAiActionProposalAction"
module: AI
status: active
tags: [contract, action, ai, proposta, human-in-the-loop]
created: 2026-11-26
updated: 2026-11-27
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

- `execute(AiActionProposal $proposal)`; la proposta deve essere nello stato `pending`.

## Output

- Se `pending`, la stessa proposta, con `status` = `cancelled`.
- Negli altri stati, `RuntimeException` e nessuna mutazione.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-CANCEL-INV-1 | Una proposta `pending` diventa `cancelled` (cambiamento in memoria testato; persistenza non testata). | `AI-PROPOSAL-INV-42` |
| AI-CANCEL-INV-2 | Nessun handler gira e `result`, `error`, `executed_at` non vengono toccati: annullare non esegue nulla. | — (non dimostrato) |
| AI-CANCEL-INV-3 | Una cancellazione tardiva/ripetuta (`confirmed`, `executed`, `failed`, `cancelled`) è rifiutata senza modificare stato. | `AI-PROPOSAL-INV-42` |

## Effetti collaterali

- Un `UPDATE` di `ai_action_proposals` (solo `status`).
- Nessuna notifica, nessun log.

## Errori

- `RuntimeException` se lo stato corrente non è `pending`.
- Errori di database sul salvataggio risalgono al chiamante.

## Non fa

- Non fornisce ancora una transizione atomica *compare-and-swap*: due richieste concorrenti su istanze stale possono attraversare il controllo insieme (#42).
- Non registra chi ha annullato né quando: non esiste `cancelled_by` né `cancelled_at`.
- Non annulla gli effetti di una proposta già eseguita.

## Limiti noti (difetti da correggere, non da imitare)

- **Il controllo dello stato è solo in memoria prima di `save()`.** Per il gate P0 serve `UPDATE ... WHERE status = pending`, verifica dell'update count, test concorrenti su DB, autorizzazione utente/tenant e riconciliazione delle azioni incomplete (#42).
- I test `tests/Unit/Actions/CancelAiActionProposalActionTest.php` usano `saving` simulato: dimostrano la macchina a stati, **non** la persistenza né la sicurezza da race.
