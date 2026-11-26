---
title: "Contratto — AppendAiMessageAction"
type: contract
action: "Modules\\AI\\Actions\\AppendAiMessageAction"
module: AI
status: active
tags: [contract, action, ai, conversazione, messaggi]
created: 2026-11-26
updated: 2026-11-26
related:
  - create-ai-thread-action.md
  - ../adr/0003-user-ids-are-strings.md
---

# AppendAiMessageAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/AppendAiMessageAction.php`. Aggiunge un messaggio a una conversazione e ne aggiorna l'ultima attività. Lo usa
`Actions/Assistant/AnswerQuestionAction` per i messaggi che non passano dal modello (domanda arrivata quando la conversazione è già
in mano a una persona, risposta di un operatore).

## Input

- `execute(AiThread $thread, AiMessageRoleEnum $role, string $content, int|string|null $userId = null, ?array $payload = null)`.
- `$role`: `user`, `assistant`, `tool` o `system`. `$content`: il testo, che per un cittadino è un suo messaggio.

## Output

- Il `AiMessage` salvato.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-MESSAGE-INV-1 | Il messaggio è salvato nel thread con ruolo, contenuto e payload dati; `user_id` si salva come stringa, `null` se assente. | — (non dimostrato) |
| AI-MESSAGE-INV-2 | Dopo il messaggio, `last_message_at` del thread vale l'istante della chiamata. | — (non dimostrato) |

## Effetti collaterali

Nell'ordine:

1. `INSERT` in `ai_messages`.
2. `UPDATE` di `ai_threads.last_message_at`.

I due salvataggi non sono in una transazione: se il secondo fallisce, il messaggio resta e il thread non risulta aggiornato.

## Errori

- Errori di database risalgono al chiamante.

## Non fa

- Non chiama il modello e non genera risposte.
- Non impone che solo i messaggi con ruolo `user` abbiano un `user_id` (l'enum lo dice nel docblock, l'Action salva quello che riceve).
- Non limita la lunghezza del contenuto né toglie dati personali.
- Non cancella né anonimizza mai: nessuna scadenza di conservazione.

## Limiti noti (difetti da correggere, non da imitare)

- I messaggi dei cittadini si conservano senza scadenza: nel modulo non c'è nessun codice di cancellazione o anonimizzazione
  (`rg -i "retention|prune|purge|anonymi"` in `Modules/AI/app` e `config`: nessun risultato). È una decisione di conservazione ancora da prendere.
- Nessun test esercita questa Action.
