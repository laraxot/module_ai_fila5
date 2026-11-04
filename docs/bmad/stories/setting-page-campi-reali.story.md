---
title: SettingPage di AI con campi reali
status: done
module: AI
type: story
created: 2026-11-04
related:
  - ../../../../Xot/docs/bmad/stories/setting-page-campi-reali.story.md
---

# SettingPage di AI con campi reali

## Fase BMAD

Build. Contratto e matrice: story madre in Xot. La pagina aveva `getFormSchema(): array { return []; }` e una
`AIConfigData` vuota.

## Campi esposti

I default sono quello che le Action facevano con la chiave non impostata (`config('ai.x', default)`, e `config('ai.x')`
valeva `NULL` perche' nessun file tenant la definiva). Nulla cambia finche' nessuno salva.

| Campo | Default | Lettore | Decisione |
|-------|---------|---------|-----------|
| `model` | `gpt-3.5-turbo-instruct` | `GeneratePredictionsAction::resolveOpenAiConfig` | esposto |
| `chat_model` | `gpt-4o-mini` | `Predict/GeneratePredictionDraftsAction::execute` | esposto |
| `temperature` | `0.7` | `GeneratePredictionsAction::resolveOpenAiConfig` | esposto |
| `chat_temperature` | `0.6` | `Predict/GeneratePredictionDraftsAction::execute` | proprieta' nuova, vedi sotto |
| `max_tokens` | `1500` | `GeneratePredictionsAction::resolveOpenAiConfig` | esposto |
| `timeout` | `30` | `MakeAIRequestAction::execute` | esposto |
| `retry_attempts` | `3` | `MakeAIRequestAction::execute` | esposto, minimo 1 (con 0 il ciclo non parte e l'Action lancia subito) |
| `openai_base_url` | `https://api.openai.com/v1` | `MakeAIRequestAction::execute` | esposto, `url()` e barra finale tolta al salvataggio perche' l'Action aggiunge `/chat/completions` |

### Due temperature

Le due Action che leggevano `ai.temperature` avevano default diversi (0.7 le previsioni a completion, 0.6 le bozze
chat) e usano modelli diversi (`model` e `chat_model`). Con una sola proprieta' uno dei due comportamenti sarebbe
cambiato in silenzio. Due proprieta': `temperature` per le previsioni, `chat_temperature` per le bozze.

## Lettori cambiati

- `app/Actions/MakeAIRequestAction.php`: `AIConfigData::make()` per base URL, timeout e tentativi. Spariti i cast grezzi
  (`is_int(...) ? ... : 30`) e le quattro `Assert` ridondanti: i tipi li garantisce la Data.
- `app/Actions/GeneratePredictionsAction.php`: modello, temperatura, token massimi dalla Data.
- `app/Actions/Predict/GeneratePredictionDraftsAction.php`: modello chat e temperatura dalla Data; rimossi
  `resolveModel()` e `resolveTemperature()` (erano il secondo posto dove vivevano i default).
- Non cambiano: `GeneratePredictionsAction` ha ancora URL e timeout fissi (`api.openai.com/v1/completions`, 30 s), e
  `MakeAIRequestAction` ha modello `gpt-4`, temperatura `0.3` e 2000 token scritti nella richiesta. Non erano
  configurabili prima, nessuno li legge da config: restano fuori finche' non c'e' una richiesta reale.

## Lasciato fuori

- `openai_api_key` (e `services.openai.api_key`): segreto, resta in `.env`. `MakeAIRequestAction` continua a leggerlo da
  `config()`. Un test verifica che nessuna proprieta' della Data contenga `api_key`.
- Rischio da sapere: `openai_base_url` e' l'indirizzo a cui parte la chiave API. La pagina e' solo super-admin, ma
  chi la salva puo' mandare la chiave a un altro host. Accettato perche' serve per i gateway compatibili OpenAI.

## Cose notate e non toccate

- `Modules/AI/config/ai.php` (config del modulo, chiave `ai.ai.*`): nessuno la legge e ha default diversi da quelli
  reali (`chat_model` `gpt-3.5-turbo`, `rate_limit.*`, `logging`). E' dato morto che un giorno qualcuno potrebbe
  scambiare per la fonte. Da rimuovere in una story a parte.
- `PredictionData::from([...])` chiama la creazione magica `fromOpenAIResponse()`, che chiama
  `OpenAiPredictionMapper::toPredictionData()`, che chiama `PredictionData::from([...])`: ricorsione infinita.
  `GeneratePredictionsAction::execute()` non puo' arrivare in fondo (Xdebug la interrompe a 512 frame; in produzione
  e' un overflow dello stack). Pre-esistente, non causato da questa story. Per questo il test del lettore usa
  `resolveOpenAiConfig()` e non `execute()`.
- `QueueableActionContractTest` e' rosso da prima: `Prompt/AIPromptTemplates` non usa `QueueableAction`.
- `tests/Unit/Actions/MakeAIRequestActionTest.php` non dichiarava `uses(TestCase::class)` e falliva con
  "Target class [config] does not exist" (4 test). Aggiunta la riga, ora e' verde.

## Test e gate

- `tests/Unit/Datas/AIConfigDataTest.php` (nuovo, 8 test): default, override del tenant, override parziale, stringhe
  numeriche del form normalizzate, file mancante, nessuna proprieta' `*api_key*`, chiavi dello schema uguali alle
  proprieta' della Data, etichette `it` e `en`. Rosso prima (7 su 8), verde dopo.
- `tests/Unit/Actions/AiSettingsAreReadTest.php` (nuovo, 8 test): i tre lettori usano i valori del file tenant e i
  default quando e' vuoto; la chiave API arriva da `services`, mai dal file tenant.
- `tests/Support/FakeTenantAiConfig.php` (nuovo): seam di test. Il contenitore risolve `GetTenantConfigArrayAction`,
  quindi si sostituisce l'istanza e `AIConfigData::make()` legge un file finto, senza scrivere in `config/{tenant}`.
- `MakeAIRequestActionTest` pilota l'URL base con la Data invece di `config(['ai.openai_base_url' => ...])`;
  `tests/TestCase.php` non imposta piu' `ai.model`, `ai.temperature`, `ai.max_tokens` (nessuno li legge).
- Pest `tests/Unit/Actions` + `tests/Unit/Datas`: vedi resoconto finale del task. PHPStan livello max: 0 errori sui file
  toccati. Pint: pulito.
