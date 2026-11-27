---
title: "Contratto — SuggestSolutionsAction"
type: contract
action: "Modules\\AI\\Actions\\SuggestSolutionsAction"
module: AI
status: active
tags: [contract, action, ai, soluzioni, servizi-esterni]
created: 2026-11-26
updated: 2026-11-26
related:
  - classify-ticket-action.md
  - make-ai-request-action.md
---

# SuggestSolutionsAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/SuggestSolutionsAction.php`. Chiede a un LLM soluzioni, misure preventive e azioni di follow-up per una
segnalazione già classificata; non scrive la segnalazione.

Stato d'uso: nessun codice di produzione la chiama (`rg SuggestSolutionsAction`: solo la classe e il costruttore dei prompt); la
esercita `tests/Unit/Actions/MakeAIRequestActionTest.php`.

## Input

- `execute(string $title, string $description, string $category)`: testo della segnalazione (scritto da un cittadino) e categoria.

## Output

- `array<string, mixed>`: `solutions`, `preventive_measures`, `follow_up_actions` come decodificati dalla risposta, oppure `DEFAULTS`
  (tre liste vuote).

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-SUGGEST-INV-1 | La risposta è in cache sotto `ai:solutions:` + `md5(title.description.category)` per 1800 s: lo stesso input non richiama il provider. | — (non dimostrato) |
| AI-SUGGEST-INV-2 | Il prompt viene dal template `solutions` con titolo, descrizione e categoria, e la richiesta passa da `MakeAIRequestAction` con tipo `solutions`. | `AI-SUGGEST-INV-2` |
| AI-SUGGEST-INV-3 | Una risposta vuota, non JSON o non oggetto restituisce `DEFAULTS`: mai un array vuoto e mai un'eccezione di decodifica. | — (non dimostrato) |

## Effetti collaterali

1. Lettura e scrittura della cache Laravel.
2. A cache vuota: chiamata HTTP al provider tramite `MakeAIRequestAction`, con titolo, descrizione e categoria nel prompt.

## Errori

- Le eccezioni di `MakeAIRequestAction` risalgono al chiamante. Nessuna validazione degli input.

## Non fa

- Non modifica la segnalazione, non crea proposte e non chiede conferma a una persona.
- Non toglie dal testo i dati personali prima di inviarlo.
- Non rispetta `ai.free_models_only` né la cascata Groq/OpenRouter.

## Limiti noti (difetti da correggere, non da imitare)

Gli stessi di [ClassifyTicketAction](classify-ticket-action.md): modello a pagamento fisso (`gpt-4`) a prescindere da
`free_models_only`, testo del cittadino nei log di `MakeAIRequestAction` quando il provider fallisce, risposta vuota messa in cache
per 30 minuti, chiave di cache senza separatore tra i campi.
