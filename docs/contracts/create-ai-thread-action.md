---
title: "Contratto — CreateAiThreadAction"
type: contract
action: "Modules\\AI\\Actions\\CreateAiThreadAction"
module: AI
status: active
tags: [contract, action, ai, conversazione]
created: 2026-11-26
updated: 2026-11-26
related:
  - append-ai-message-action.md
  - ../adr/0003-user-ids-are-strings.md
---

# CreateAiThreadAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/CreateAiThreadAction.php`. Apre una conversazione con l'AI (un `AiThread`) a cui poi si aggiungono messaggi e
proposte.

Stato d'uso: nessun codice di produzione la chiama (`rg CreateAiThreadAction`: solo la classe). È un punto di ingresso per i
progetti ospite.

## Input

- `execute(int|string|null $userId, string $panelId, array $meta = [])`: chi apre (intero, UUID o `null` per un visitatore anonimo),
  da dove arriva la conversazione (`operator`, `widget`, …: lo decide il chiamante) e metadati liberi.

## Output

- Il `AiThread` salvato.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-THREAD-INV-1 | Ogni thread ha un `public_id` UUID v4 nuovo. | — (non dimostrato) |
| AI-THREAD-INV-2 | `created_by_user_id` si salva come stringa; `null` resta `null` (visitatore anonimo). | — (non dimostrato) (la colonna stringa è provata da `tests/Feature/UserIdColumnsAcceptUuidTest.php`, non l'Action) |
| AI-THREAD-INV-3 | `meta` vuoto si salva come `null`, non come array vuoto. | — (non dimostrato) |

## Effetti collaterali

- Un `INSERT` in `ai_threads`. Nessuna notifica, nessun log.

## Errori

- Errori di database risalgono al chiamante.

## Non fa

- Non apre il primo messaggio (`AppendAiMessageAction`) e non imposta `last_message_at`.
- Non valida `panelId` (stringa libera) né il contenuto di `meta`.
- Non verifica che l'utente esista.

## Limiti noti (difetti da correggere, non da imitare)

- `meta` è salvato com'è: un chiamante può metterci dati personali senza che nessuno lo controlli.
- Un thread anonimo e uno di un utente si distinguono solo per `created_by_user_id` nullo.
- Nessun test esercita questa Action.
