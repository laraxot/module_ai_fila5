---
title: "Contratto — ConfirmAiActionProposalAction"
type: contract
action: "Modules\\AI\\Actions\\ConfirmAiActionProposalAction"
module: AI
status: accepted
updated: 2026-11-26
---

# ConfirmAiActionProposalAction

Classe: `Modules\\AI\\Actions\\ConfirmAiActionProposalAction` · `@contract docs/contracts/confirm-ai-action-proposal-action.md`

Religione: l'AI propone, una persona conferma, un handler di dominio esegue ([ADR](../adr/ai-proposes-human-confirms.md)).

## Input

- `AiActionProposal $proposal` — proposta da confermare.
- `int|string $confirmedByUserId` — utente umano che conferma.

## Output

- La stessa `AiActionProposal` aggiornata (`confirmed` → `executed` o `failed`).

## Invarianti

| ID | Regola | Verifica |
|---|---|---|
| AI-CONFIRM-INV-1 | Prima dell'esecuzione lo stato passa a `confirmed` con `confirmed_by_user_id` e `confirmed_at`. | `->group('AI-CONFIRM-INV-1')` |
| AI-CONFIRM-INV-2 | Se nessun handler è registrato per `type`, la proposta finisce `failed` con errore esplicito e non esegue side effect di dominio. | `->group('AI-CONFIRM-INV-2')` |
| AI-CONFIRM-INV-3 | Successo → `executed` + `result`; eccezione handler → `failed` + `error` (mai silenziosa). | `->group('AI-CONFIRM-INV-3')` |

## Effetti collaterali

- Persistenza stati sulla proposta; esecuzione dell'handler di dominio registrato.

## Errori

- Handler assente → `failed` (non eccezione).
- Throwable dall'handler → catturato, proposta `failed`.

## Non fa

- Non crea proposte (`CreateAiActionProposalAction`).
- Non conosce i moduli di dominio: solo il registry.
