---
title: "Contratto — ConfirmAiActionProposalAction"
type: contract
action: "Modules\\AI\\Actions\\ConfirmAiActionProposalAction"
module: AI
status: active
tags: [contract, action, ai, proposta, human-in-the-loop]
created: 2026-11-26
updated: 2026-11-26
related:
  - ../adr/0001-ai-proposes-human-confirms.md
  - ../adr/0003-user-ids-are-strings.md
  - create-ai-action-proposal-action.md
  - cancel-ai-action-proposal-action.md
---

# ConfirmAiActionProposalAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/ConfirmAiActionProposalAction.php`. L'AI propone, una persona conferma, un handler di dominio esegue:
perché è nell'[ADR](../adr/0001-ai-proposes-human-confirms.md).

Ripristinato il 2026-11-26 dopo la cancellazione nel repository del modulo (commit `2471438`): il docblock dell'Action, l'ADR e i
group dei test continuavano a citarlo. Chiamante di produzione: la tabella delle proposte
(`Filament/Resources/AiActionProposalResource/Tables/AiActionProposalsTable.php`).

## Input

- `execute(AiActionProposal $proposal, int|string $confirmedByUserId)`: la proposta e l'id di chi conferma (intero o UUID).
- Implicito: l'`AiActionHandlerRegistry` (costruttore) che associa `type` → handler di dominio.

## Output

- La stessa `AiActionProposal`, aggiornata: `executed` o `failed` (mai `pending`).

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-CONFIRM-INV-1 | `confirmed_by_user_id` conserva l'id come stringa intera (un UUID non viene troncato) e `confirmed_at` è valorizzato. | `AI-CONFIRM-INV-1` |
| AI-CONFIRM-INV-2 | Con un handler registrato per `type` la proposta finisce `executed`, con `executed_at` e il `result` restituito dall'handler. | `AI-CONFIRM-INV-2` |
| AI-CONFIRM-INV-3 | Se nessun handler è registrato per `type`, o se l'handler fallisce, la proposta finisce `failed` con `error` valorizzato e, nel caso senza handler, con `executed_at` nullo; l'Action non lancia. | `AI-CONFIRM-INV-3` |
| AI-CONFIRM-INV-4 | Un'eccezione dell'handler non risale al chiamante: il messaggio dell'eccezione finisce in `error`. | `AI-CONFIRM-INV-4` |
| AI-CONFIRM-INV-5 | Lo stato `confirmed` (con chi e quando) è salvato prima di cercare ed eseguire l'handler: se il processo muore a metà, la proposta resta `confirmed`, mai `pending` con effetti già avvenuti. | — (non dimostrato) |

Test: `tests/Unit/Actions/ConfirmAiActionProposalActionTest.php` (registry e handler finti). INV-3 è provato per il caso senza handler
(`executed_at` nullo) e, per l'handler che fallisce, solo per lo stato `failed` e il messaggio.

## Effetti collaterali

Nell'ordine:

1. `UPDATE` della proposta: `status` = `confirmed`, `confirmed_by_user_id`, `confirmed_at`.
2. `AiActionHandlerContract::handle($proposal)`: gli effetti di dominio li decide l'handler del `type` (può scrivere su altri moduli).
3. `UPDATE` della proposta: `executed` + `executed_at` + `result`, oppure `failed` + `error`.

Nessuna notifica, nessun evento, nessuna transazione: i due `UPDATE` e gli effetti dell'handler non sono atomici.

## Errori

- Nessuna eccezione per handler assente o fallito (INV-3, INV-4): l'esito è nello stato della proposta.
- Gli errori di database sui salvataggi risalgono al chiamante.

## Non fa

- Non crea né annulla proposte (`CreateAiActionProposalAction`, `CancelAiActionProposalAction`).
- Non verifica che `$confirmedByUserId` sia una persona né che abbia il diritto di confermare: lo decide il chiamante.
- Non conosce i moduli di dominio: parla solo col registry.
- Non riprova un handler fallito.

## Limiti noti (difetti da correggere, non da imitare)

- **Nessun controllo sullo stato di partenza.** Il codice (righe 32-36) imposta `confirmed` qualunque sia lo stato: una proposta
  `executed`, `failed` o `cancelled` si può riconfermare e l'handler gira di nuovo (doppia esecuzione). L'ADR descrive il ciclo
  `pending` → `confirmed` → `executed|failed`, ma nessuna riga lo impone.
- `error` salva `$throwable->getMessage()` grezzo: può contenere dati personali o testo di query.
- Il messaggio per handler assente («No AiActionHandler registered…») è in inglese, scritto nel codice.
