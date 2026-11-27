---
title: "Contratto — ConfirmAiActionProposalAction"
type: contract
action: "Modules\\AI\\Actions\\ConfirmAiActionProposalAction"
module: AI
status: active
tags: [contract, action, ai, proposta, human-in-the-loop]
created: 2026-11-26
updated: 2026-11-27
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

## Input

- `execute(AiActionProposal $proposal, int|string $confirmedByUserId)`: la proposta e l'id di chi conferma (intero o UUID).
- Implicito: l'`AiActionHandlerRegistry` (costruttore) che associa `type` → handler di dominio.

## Output

- La stessa `AiActionProposal`, aggiornata: `executed` o `failed` (mai lasciata `pending` dopo un confirm riuscito).
- Se lo stato di partenza non è `pending`, l'Action **lancia** `RuntimeException` e non esegue l'handler.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-CONFIRM-INV-1 | `confirmed_by_user_id` conserva l'id come stringa intera (un UUID non viene troncato) e `confirmed_at` è valorizzato. | `AI-CONFIRM-INV-1` |
| AI-CONFIRM-INV-2 | Con un handler registrato per `type` la proposta finisce `executed`, con `executed_at` e il `result` restituito dall'handler. | `AI-CONFIRM-INV-2` |
| AI-CONFIRM-INV-3 | Se nessun handler è registrato per `type`, o se l'handler fallisce, la proposta finisce `failed` con `error` valorizzato e, nel caso senza handler, con `executed_at` nullo; l'Action non lancia per fallimenti handler. | `AI-CONFIRM-INV-3` |
| AI-CONFIRM-INV-4 | Un'eccezione dell'handler non risale al chiamante: il messaggio dell'eccezione finisce in `error`. | `AI-CONFIRM-INV-4` |
| AI-CONFIRM-INV-5 | Solo `PENDING` può essere confermato. Stati `executed` / `failed` / `cancelled` / `confirmed` producono `RuntimeException` e **zero** chiamate all'handler. | `AI-CONFIRM-INV-5`, `AI-PROSPECTING-INV-42` |
| AI-CONFIRM-INV-6 | Se `$proposal->exists`, il passaggio a `confirmed` avviene con UPDATE condizionale `WHERE status = pending` (compare-and-swap). Se `affected !== 1`, RuntimeException (lost race). Path in-memory (`!exists`) usa `forceFill`+`save` per i test unitari. | — (host DB concurrent ancora da dimostrare) |

Test: `tests/Unit/Actions/ConfirmAiActionProposalActionTest.php` (registry e handler finti).

## Effetti collaterali

Nell'ordine:

1. Guardia di stato + (se persisted) CAS `pending` → `confirmed` con `confirmed_by_user_id` / `confirmed_at`.
2. `AiActionHandlerContract::handle($proposal)`: effetti di dominio dell'handler del `type`.
3. `UPDATE` della proposta: `executed` + `executed_at` + `result`, oppure `failed` + `error`.

Nessuna notazione, nessun evento obbligatorio. I due aggiornamenti e gli effetti dell'handler **non** sono in un'unica transazione di dominio (l'handler può toccare altri moduli).

## Errori

- `RuntimeException` se stato ≠ `PENDING` o CAS fallisce (INV-5, INV-6).
- Nessuna eccezione per handler assente o fallito (INV-3, INV-4): l'esito è nello stato della proposta.
- Gli errori di database sui salvataggi risalgono al chiamante.

## Non fa

- Non crea né annulla proposte (`CreateAiActionProposalAction`, `CancelAiActionProposalAction`).
- Non verifica che `$confirmedByUserId` sia una persona né che abbia il diritto di confermare: lo decide il chiamante.
- Non conosce i moduli di dominio: parla solo col registry.
- Non riprova un handler fallito.

## Limiti noti

- CAS e path `exists` richiedono suite host con DB reale per chiudere INV-6.
- `error` salva ancora `$throwable->getMessage()` grezzo: possibile testo sensibile.
- Messaggio handler assente in inglese, hardcoded.

## Riconciliazione documentale (2026-11-27)

Questo file aveva un conflitto Git irrisolto. La base è la variante più recente del contratto, con il riferimento a `app/Actions/ConfirmAiActionProposalAction.php` **esistente su `dev`**; la sua semantica, i group Pest citati e gli eventuali effetti non sono stati rieseguiti in questa sessione. Il confronto con il codice e i test resta il gate ECC della story owner. [Tracciamento root #15](https://github.com/laraxot/base_ptvx_fila5/issues/15).
