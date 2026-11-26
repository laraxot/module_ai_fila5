---
title: "ADR — L'AI propone, una persona conferma, un handler del dominio esegue"
type: decision
status: accepted
module: AI
decision-makers: AI module owner
tags: [adr, ai, proposte, human-in-the-loop, registry]
created: 2026-11-24
updated: 2026-11-26
related:
  - ../contracts/confirm-ai-action-proposal-action.md
  - ../agents.md
---

# ADR: l'AI propone, una persona conferma, un handler del dominio esegue

## Contesto

- Un agente AI che agisce sui dati (timbrature, stati di ordini) può sbagliare: un'azione eseguita non si annulla con un prompt.
- Il modulo AI è condiviso tra progetti con domini diversi; l'agente operatore portato da un altro gestionale (~1575 righe,
  tool-calling verso `WorkOrder`, `Bom`, `Product`, …) dipendeva direttamente da moduli che qui non esistono o hanno altro schema
  (`docs/deferred-operator-assistant-agent.md`, cancellato dal commit `2471438` del repository del modulo; si legge con `git -C laravel/Modules/AI show 2471438^:docs/deferred-operator-assistant-agent.md`).
- Domanda: come lasciare che l'AI faccia cose, senza che agisca da sola e senza che il modulo AI conosca i domini?

## Driver della decisione

- Sicurezza dei dati: un'azione eseguita da un modello non si annulla con un prompt, quindi serve un punto in cui una persona possa fermarla.
- Accoppiamento: il modulo AI è condiviso tra progetti con domini diversi e non deve importare i loro moduli.
- Tracciabilità: chi ha proposto, chi ha confermato, quando e con che esito devono restare sul record.
- Un solo esecutore per tipo di azione: lo stesso `type` non deve poter essere eseguito da due handler.

## Alternative scartate

- Tool-calling che scrive direttamente (come l'agente originale): nessun punto in cui una persona possa fermare un errore del modello.
- Portare l'agente 1:1: tool contro modelli che qui non esistono, accoppiamento AI → domini.
- Eventi Laravel al posto del registry: più handler potrebbero eseguire la stessa proposta; con il registry il `type` ha un solo handler.

## Decisione

- L'AI non esegue: crea una `AiActionProposal` (`CreateAiActionProposalAction`, stato `pending`, `type` + `payload` + `preview`).
- Una persona la conferma o la annulla dal back office (`AiActionProposalResource`, azioni visibili solo se `canBeConfirmed()` /
  `canBeCancelled()`).
- `ConfirmAiActionProposalAction` cerca l'handler del `type` in `Adapters\AiActionHandlerRegistry` (singleton in `AIServiceProvider`) e lo
  esegue; esito e errore restano sulla proposta (`executed`/`failed`, `result`/`error`).
- Gli handler (`Contracts\AiActionHandlerContract`) li registra il modulo di dominio del progetto: il modulo AI non importa nessun modulo
  di dominio.

## Conseguenze

- Pro: traccia completa (chi ha proposto, chi ha confermato, quando, esito); il modulo AI resta agnostico.
- Contro: ogni azione chiede un clic; serve un handler per ogni `type`.
- Debito: `ConfirmAiActionProposalAction` non controlla lo stato di partenza (lo fa solo la UI) e non apre una transazione intorno
  all'handler.

## Conferma

- `cd laravel && vendor/bin/pest Modules/AI/tests --group=AI-CONFIRM-INV-3`: fallisce se una proposta senza handler non finisce `failed`
  (e `--group=AI-CONFIRM-INV-4` per l'handler in errore). Registry: `Modules/AI/tests/Unit/Support/AiActionHandlerRegistryTest.php`.
- Regola di review, controllata il 2026-11-26: `rg --no-filename -o "^use Modules\\[A-Za-z]+" Modules/AI/app | sort | uniq -c` mostra solo
  `AI`, `UI` e `Xot`: nessun modulo di dominio importato per eseguire azioni.
- Nessuna conferma automatica per la parte «lo stato di partenza è `pending`»: vedi «Stato attuale».

## Stato attuale

L'ADR descrive il ciclo `pending` → `confirmed` → `executed|failed`, ma il codice non lo impone: `ConfirmAiActionProposalAction` e
`CancelAiActionProposalAction` non controllano lo stato di partenza (lo fa soltanto la tabella Filament con `canBeConfirmed()` e
`canBeCancelled()`). Una proposta già `executed` si può riconfermare e l'handler gira di nuovo. Dettaglio nei contratti:
[conferma](../contracts/confirm-ai-action-proposal-action.md), [annullamento](../contracts/cancel-ai-action-proposal-action.md).
