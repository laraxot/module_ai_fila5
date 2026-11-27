---
title: "Contratto — OpenAiCompatibleRequestAction"
type: contract
action: "Modules\\AI\\Actions\\Vision\\OpenAiCompatibleRequestAction"
module: AI
status: active
tags: [contract, action, ai, provider, servizi-esterni]
created: 2026-11-26
updated: 2026-11-26
related:
  - request-assistant-completion-action.md
  - make-ai-request-action.md
---

# OpenAiCompatibleRequestAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/Vision/OpenAiCompatibleRequestAction.php`. Un `POST` JSON verso un fornitore compatibile OpenAI (OpenRouter,
Groq, server locale) con la chiave del fornitore. Lo usano `RequestAssistantCompletionAction`, `Vision/EditImageAction` e
`Vision/ExtractStructuredDataFromImagesAction`.

## Input

- `execute(AiProviderEnum $provider, string $path, array $payload, int $timeout)`: fornitore, percorso dopo la base URL (es.
  `/chat/completions`), corpo JSON e timeout di base (secondi).
- Implicito: la chiave letta da `config($provider->keyConfig())` (`services.groq.key`, `services.openrouter.key`, …), che viene da `.env`.

## Output

- `array<string, mixed>`: il JSON della risposta, così com'è.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-HTTP-INV-1 | Un fornitore che richiede la chiave (tutti tranne `local`) con chiave assente o vuota lancia `AiRequestException` che nomina la variabile d'ambiente, non il valore, e non parte nessuna richiesta. | — (non dimostrato) (prova indiretta: `tests/Unit/Vision/VisionActionsTest.php`, «fails clearly without any API key») |
| AI-HTTP-INV-2 | Il fornitore `local` senza chiave funziona: usa il Bearer fittizio `local` e non dà errore. | — (non dimostrato) (prova indiretta: `VisionActionsTest`, «reads images through a local OpenAI-compatible server without an API key») |
| AI-HTTP-INV-3 | Solo OpenRouter riceve `HTTP-Referer` e `X-Title` (`app.url`, `app.name`); gli altri fornitori no. | — (non dimostrato) |
| AI-HTTP-INV-4 | Errore di rete, risposta non 2xx o corpo non JSON lanciano `AiRequestException` con il nome del fornitore; per la risposta non 2xx il codice HTTP e, se c'è, `error.message` del fornitore. | — (non dimostrato) (prova indiretta: `VisionActionsTest`, «turns an HTTP error into an AiRequestException with the provider message») |

## Effetti collaterali

- Un `POST` HTTPS a `<baseUrl del fornitore><path>` con `Authorization: Bearer <chiave>` e il `payload` nel corpo (per l'assistente: la
  domanda del cittadino e il prompt di sistema; per la visione: le immagini).
- Nessuna scrittura locale, nessun log, nessuna cache.

## Errori

- Sempre `AiRequestException` (mai l'eccezione HTTP di Laravel): chiave mancante, servizio non raggiungibile, errore HTTP, risposta
  non JSON. Il codice della eccezione è lo status HTTP quando c'è.

## Non fa

- Non riprova e non passa al fornitore successivo: la cascata è del chiamante.
- Non sceglie il modello e non applica `ai.free_models_only`: lo decidono i chiamanti.
- Non toglie dal `payload` dati personali.
- Non scrive log: lo fa il chiamante con il messaggio dell'eccezione.

## Limiti noti (difetti da correggere, non da imitare)

- `error.message` del fornitore finisce nel messaggio dell'eccezione e, per l'assistente, nel log (`Log::warning` in
  `RequestAssistantCompletionAction`): il testo del fornitore può citare parti del prompt.
- I messaggi dell'eccezione sono italiani, scritti nel codice (non passano dai file di lingua).
- Nessun test esercita questa Action direttamente: le prove sono indirette, passando dalla visione.
