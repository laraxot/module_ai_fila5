---
title: "Contratto — ClassifyTicketAction"
type: contract
action: "Modules\\AI\\Actions\\ClassifyTicketAction"
module: AI
status: active
tags: [contract, action, ai, classificazione, servizi-esterni]
created: 2026-11-26
updated: 2026-11-26
related:
  - suggest-solutions-action.md
  - make-ai-request-action.md
---

# ClassifyTicketAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/ClassifyTicketAction.php`. Suggerisce categoria e tag di una segnalazione chiedendoli a un LLM; non scrive la
segnalazione.

Stato d'uso: nessun codice di produzione la chiama (`rg ClassifyTicketAction`: solo la classe e il costruttore dei prompt in
`Actions/Prompt`); la esercita `tests/Unit/Actions/MakeAIRequestActionTest.php`.

## Input

- `execute(string $title, string $description)`: titolo e descrizione della segnalazione, cioè testo scritto da un cittadino.

## Output

- `array<string, mixed>`: i campi decodificati dalla risposta (`category`, `subcategory`, `confidence`, `tags`, `urgency_indicators`, …)
  oppure `DEFAULTS` (`altro` / `generale` / `0.5` / liste vuote) se la risposta non è un oggetto JSON.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-CLASSIFY-INV-1 | La risposta è in cache sotto `ai:classification:` + `md5(title.description)` per 3600 s: lo stesso testo non richiama il provider. | — (non dimostrato) |
| AI-CLASSIFY-INV-2 | Il prompt viene dal template `classification` con titolo e descrizione, e la richiesta passa da `MakeAIRequestAction` con tipo `classification`; un oggetto JSON valido torna decodificato. | `AI-CLASSIFY-INV-2` |
| AI-CLASSIFY-INV-3 | Una risposta vuota, non JSON o non oggetto restituisce `DEFAULTS`: mai un array vuoto e mai un'eccezione di decodifica. | — (non dimostrato) |

## Effetti collaterali

1. Lettura e scrittura della cache Laravel (store di default).
2. A cache vuota: chiamata HTTP al provider tramite `MakeAIRequestAction`, **con titolo e descrizione della segnalazione nel
   prompt** (dato personale verso un terzo: OpenAI, vedi i limiti).

## Errori

- Le eccezioni di `MakeAIRequestAction` (rete, tentativi esauriti) risalgono al chiamante; in coda diventano un job fallito.
- Nessuna validazione: titolo o descrizione vuoti partono comunque.

## Non fa

- Non aggiorna `Ticket` né crea proposte AI; non chiede conferma a una persona.
- Non decide la priorità operativa: restituisce solo indicatori suggeriti dal modello.
- Non toglie dal testo nomi, indirizzi o altri dati personali prima di inviarlo.
- Non rispetta `ai.free_models_only` né la cascata Groq/OpenRouter: usa sempre `MakeAIRequestAction`.

## Limiti noti (difetti da correggere, non da imitare)

- **Provider a pagamento e dati a un terzo.** `MakeAIRequestAction` (riga 58) manda tutto a `gpt-4` sull'endpoint OpenAI, mentre il
  progetto prescrive modelli gratuiti (`AIConfigData::$free_models_only` vale `true` di default) e questa Action non lo consulta.
- **Dati personali nei log.** Se il provider fallisce, `MakeAIRequestAction` scrive titolo, descrizione e corpo della risposta nel log
  applicativo (`$context`, righe 90-101 di `MakeAIRequestAction`).
- La cache salva la stringa grezza, anche vuota: una risposta senza contenuto (HTTP 2xx senza `choices`) resta in cache un'ora e
  l'Action risponde `DEFAULTS` senza richiamare il provider.
- La chiave di cache concatena titolo e descrizione senza separatore: `("ab", "c")` e `("a", "bc")` collidono.
