---
title: "Contratto — GeneratePredictionsAction"
type: contract
action: "Modules\\AI\\Actions\\GeneratePredictionsAction"
module: AI
status: active
tags: [contract, action, ai, openai, legacy, servizi-esterni]
created: 2026-11-26
updated: 2026-11-26
related:
  - make-ai-request-action.md
---

# GeneratePredictionsAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/GeneratePredictionsAction.php`. Chiede a OpenAI di generare i dati di una previsione per un mercato di
previsioni (titolo, descrizione, categoria, tag, parametri di mercato) e li valida.

**Comportamento legacy**: non è una funzione di FixCity. Nessun codice di produzione la chiama (`rg GeneratePredictionsAction`: solo la
classe, `Datas/AIConfigData.php` e i file di lingua `lang/*/setting.php`). Non è il modello per codice nuovo.

## Input

- `execute(string $topic, array $options = [])`: l'argomento e le opzioni libere del prompt.
- Impliciti: `services.openai.api_key` e `AIConfigData` del tenant (`model`, `temperature`, `max_tokens`).

## Output

- `PredictionData` validato.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-PREDICT-INV-1 | Modello, temperatura e `max_tokens` vengono dalle impostazioni del tenant (default `gpt-3.5-turbo-instruct`, 0,7, 1500). | `AI-PREDICT-INV-1` |
| AI-PREDICT-INV-2 | La chiave API viene sempre da `services.openai.api_key`, mai dal file del tenant. | `AI-PREDICT-INV-2` |
| AI-PREDICT-INV-3 | La risposta è validata prima di restituirla: campi obbligatori, `closed_at` data valida e futura, `tags` array, `liquidity_parameter` numerico tra 0 e 1; altrimenti `InvalidArgumentException`. | — (non dimostrato) (`tests/Unit/Actions/GeneratePredictionsActionTest.pest.php` esiste, ma il suffisso `.pest.php` non è quello che Pest raccoglie per cartella: non si sa se gira) |
| AI-PREDICT-INV-4 | Una risposta HTTP non 2xx lancia `RuntimeException` con il corpo della risposta. | — (non dimostrato) |

Test: `tests/Unit/Actions/AiSettingsAreReadTest.php` legge la configurazione che l'Action costruirebbe (`resolveOpenAiConfig` via
riflessione): il suo commento (righe 29-32) dice che `execute()` non è percorribile fino in fondo perché `PredictionData::from()`
richiama `fromOpenAIResponse()`, che richiama `from()` (ricorsione). Non l'ho verificato.

## Effetti collaterali

- Un `POST` HTTPS a `https://api.openai.com/v1/completions` (indirizzo scritto nel codice, riga 145), timeout 30 s, nessun nuovo tentativo.
- `Log::info` con `topic` e **tutte** le `options` (riga 46); `Log::debug` con i dati generati e validati (riga 244); `Log::error` con
  lo status e il **corpo della risposta** se la richiesta fallisce (riga 156).

## Errori

- `RuntimeException` per risposta non 2xx (con il corpo nel messaggio); `InvalidArgumentException` per dati non validi; errori di
  rete e di decodifica JSON risalgono.

## Non fa

- Non salva la previsione né crea record.
- Non usa `free_models_only` né la cascata dei fornitori: parla solo con OpenAI, a pagamento.
- Non riprova.

## Limiti noti (difetti da correggere, non da imitare)

- **Possibile ricorsione infinita**: secondo il commento del test, `PredictionData::from()` ↔ `fromOpenAIResponse()` si richiamano a
  vicenda e `execute()` non arriva alla fine. Da verificare con un test che la esegua davvero.
- L'indirizzo OpenAI è fisso nel codice: il tenant può cambiare `openai_base_url` per `MakeAIRequestAction`, non per questa Action.
- Il corpo della risposta del provider entra nel messaggio dell'eccezione e nel log.
- Il file di test ha suffisso `.pest.php`: probabilmente non viene raccolto da `vendor/bin/pest Modules/AI/tests`.
