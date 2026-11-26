---
title: "Contratto — ClassifyTicketAction"
type: contract
action: "Modules\\AI\\Actions\\ClassifyTicketAction"
module: AI
status: active
tags: [contract, action, ai, classification]
created: 2026-11-26
updated: 2026-11-26
---

# ClassifyTicketAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/ClassifyTicketAction.php`. Suggerisce categoria/tag di una segnalazione via LLM;
non scrive il ticket. Documentato in `docs/queueable-actions.md` solo come elenco, senza contratto.

## Input

- `string $title`, `string $description`: testo da classificare.

## Output

- `array` strutturato (`category`, `subcategory`, `confidence`, `tags`, `urgency_indicators`, …) oppure i default
  (`altro` / `generale` / `0.5` / liste vuote) se la risposta non è JSON valido.

## Invarianti

| ID | Regola | Verifica |
|---|---|---|
| AI-CLASSIFY-INV-1 | La chiave cache è `ai:classification:` + `md5(title+description)` con TTL 3600 s: stesso testo non richiama il provider. | — (non dimostrato) |
| AI-CLASSIFY-INV-2 | Prompt da template `classification` via `BuildAIPromptAction`; richiesta via `MakeAIRequestAction`. | — (non dimostrato) |
| AI-CLASSIFY-INV-3 | JSON assente o non oggetto → restituisce `DEFAULTS`, mai un array vuoto o un throw di decode. | — (non dimostrato) |

## Effetti collaterali

- Lettura/scrittura cache Laravel.
- Chiamata HTTP al provider AI (solo a cache miss) tramite `MakeAIRequestAction`.

## Errori

- Eccezioni di `MakeAIRequestAction` / rete risalgono al chiamante (anche in coda se dispatchata).
- Nessuna validazione di titolo/descrizione vuoti.

## Non fa

- Non aggiorna `Ticket` né proposte AI.
- Non richiede conferma umana (quella è `ConfirmAiActionProposalAction` su altri flussi).
- Non decide urgenza operativa del back office: restituisce solo indicatori suggeriti.
