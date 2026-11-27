---
title: "Contratto — ConfirmAiActionProposalAction"
type: contract
<<<<<<< .merge_file_gu49yl
action: "Modules\AI\Actions\ConfirmAiActionProposalAction"
tags: [contract, action, ai, proposta, human-in-the-loop]
created: 2026-11-24
updated: 2026-11-24
=======
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
>>>>>>> .merge_file_sfrAR8
---

# ConfirmAiActionProposalAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
<<<<<<< .merge_file_gu49yl
Sorgente: `app/Actions/ConfirmAiActionProposalAction.php`. Perché l'AI propone e una persona conferma:
[ADR](../adr/ai-proposes-human-confirms.md).

## Input

- La proposta (`AiActionProposal`, con `type` e `payload`).
- L'id di chi conferma (`int|string`: gli utenti sono UUID).
- Implicito: gli handler registrati in `Adapters\AiActionHandlerRegistry` (singleton).

Chiamata dall'azione «Conferma» di `Filament/Resources/AiActionProposalResource/Tables/AiActionProposalsTable.php`, visibile solo
se `status->canBeConfirmed()`.

## Output

- La stessa proposta, salvata, con lo stato finale: `executed` o `failed`.
=======
Sorgente: `app/Actions/ConfirmAiActionProposalAction.php`. L'AI propone, una persona conferma, un handler di dominio esegue:
perché è nell'[ADR](../adr/0001-ai-proposes-human-confirms.md).

## Input

- `execute(AiActionProposal $proposal, int|string $confirmedByUserId)`: la proposta e l'id di chi conferma (intero o UUID).
- Implicito: l'`AiActionHandlerRegistry` (costruttore) che associa `type` → handler di dominio.

## Output

- La stessa `AiActionProposal`, aggiornata: `executed` o `failed` (mai lasciata `pending` dopo un confirm riuscito).
- Se lo stato di partenza non è `pending`, l'Action **lancia** `RuntimeException` e non esegue l'handler.
>>>>>>> .merge_file_sfrAR8

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
<<<<<<< .merge_file_gu49yl
| AI-CONFIRM-INV-1 | Prima di eseguire si salvano `confirmed`, `confirmed_by_user_id` (stringa) e `confirmed_at`: chi ha confermato resta anche se l'handler fallisce. | `AI-CONFIRM-INV-1` |
| AI-CONFIRM-INV-2 | Esito positivo → `executed`, `executed_at`, `result` = ritorno dell'handler. | `AI-CONFIRM-INV-2` |
| AI-CONFIRM-INV-3 | Nessun handler per il `type`, o handler che lancia → `failed` con il messaggio in `error`. | `AI-CONFIRM-INV-3` |
| AI-CONFIRM-INV-4 | Nessuna eccezione dell'handler arriva al chiamante (`catch (\Throwable)`). | `AI-CONFIRM-INV-4` |

Test: `tests/Unit/Actions/ConfirmAiActionProposalActionTest.php` (salvataggio bloccato dall'evento `saving`, si prova la macchina a stati).

## Effetti collaterali

- Due `save()` della proposta (conferma, esito).
- Quello che fa l'handler del dominio registrato per il `type` (fuori dal modulo AI).

## Errori

- Solo errori del database al salvataggio della proposta.

## Non fa

- Non controlla lo stato di partenza: confermare una proposta già eseguita la riesegue. Il controllo sta solo nella UI
  (`canBeConfirmed()`); un chiamante nuovo deve farlo da sé.
- Non verifica permessi di chi conferma.
- Non apre una transazione intorno all'handler e non ritenta.
- Non crea né annulla proposte (`CreateAiActionProposalAction`, `CancelAiActionProposalAction`).
=======
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
>>>>>>> .merge_file_sfrAR8
