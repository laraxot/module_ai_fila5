---
title: "Contratto — CreateAiActionProposalAction"
type: contract
action: "Modules\\AI\\Actions\\CreateAiActionProposalAction"
module: AI
status: active
tags: [contract, action, ai, proposta, human-in-the-loop]
created: 2026-11-26
updated: 2026-11-26
related:
  - ../adr/0001-ai-proposes-human-confirms.md
  - ../adr/0003-user-ids-are-strings.md
  - confirm-ai-action-proposal-action.md
  - cancel-ai-action-proposal-action.md
---

# CreateAiActionProposalAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/CreateAiActionProposalAction.php`. Registra ciò che l'AI vorrebbe fare, in attesa che una persona lo confermi:
perché è nell'[ADR](../adr/0001-ai-proposes-human-confirms.md). Chiamante di produzione: `Actions/Assistant/AnswerQuestionAction.php`.

## Input

- `execute(AiThread $thread, int|string|null $proposedByUserId, string $type, array $payload, ?string $preview = null)`.
- `$type`: il tipo che sceglie l'handler alla conferma. `$payload`: i dati per l'handler. `$preview`: il testo che vede la persona.

## Output

- La `AiActionProposal` appena salvata.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-PROPOSE-INV-1 | La proposta nasce sempre `pending`, con un `public_id` UUID v4 nuovo: chi chiama non può imporre né lo stato né l'id. | — (non dimostrato) |
| AI-PROPOSE-INV-2 | `proposed_by_user_id` si salva come stringa (UUID o intero in forma di testo); `null` resta `null`. | — (non dimostrato) (la colonna stringa è provata da `tests/Feature/UserIdColumnsAcceptUuidTest.php`, non l'Action) |
| AI-PROPOSE-INV-3 | `type`, `payload` e `preview` si salvano come arrivano: non si controlla che esista un handler per `type` né che il `payload` sia valido. | — (non dimostrato) |

## Effetti collaterali

- Un `INSERT` in `ai_action_proposals`, legato al thread (`ai_thread_id`).
- Nessuna esecuzione, nessuna notifica, nessun log.

## Errori

- Errori di database (thread inesistente, vincoli) risalgono al chiamante.

## Non fa

- Non conferma né esegue: `ConfirmAiActionProposalAction`. Non annulla: `CancelAiActionProposalAction`.
- Non verifica che chi propone abbia il diritto di proporre quel tipo di azione.
- Non verifica che `preview` descriva davvero `payload`.

## Limiti noti (difetti da correggere, non da imitare)

- Un `type` senza handler si può proporre: il difetto compare solo alla conferma (`failed`), quando una persona ha già confermato.
- La persona che conferma decide su ciò che legge in `preview`; il `payload` che l'handler eseguirà può dire altro, e nulla in
  questa Action li confronta.
- Nessun test esercita questa Action (`tests/Unit/Models/AiActionProposalTest.php` prova i cast del modello, non l'Action).
