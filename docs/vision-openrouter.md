---
title: "Immagini con OpenRouter e Groq: estrazione strutturata e modifica"
type: guide
status: active
created: 2026-11-16
updated: 2026-11-16
module: AI
qmd: "openrouter vision immagini json schema structured outputs edit image logo agnostico"
---

# Immagini con OpenRouter e Groq

Due azioni **agnostiche**: non sanno cosa c'è nelle immagini. Le istruzioni e lo schema li passa il
modulo che le usa (es. Restaurant: foto del menu cartaceo → sezioni e piatti; foto del logo → logo
pulito). Il modulo AI non conosce menu, ristoranti o piatti.

| Azione | Endpoint | Restituisce |
|---|---|---|
| `Actions\Vision\ExtractStructuredDataFromImagesAction` | `/chat/completions` di OpenRouter o Groq, in ordine | array conforme allo schema JSON passato |
| `Actions\Vision\EditImageAction` | `/images` di OpenRouter (Groq non genera immagini) | `['mime' => ..., 'bytes' => ...]` |

```php
$data = app(ExtractStructuredDataFromImagesAction::class)->execute(
    imagePaths: [$foto1, $foto2],
    instructions: 'Leggi le voci del menu...',
    schemaName: 'menu',
    schema: ['type' => 'object', 'properties' => [...], 'required' => [...], 'additionalProperties' => false],
);

['mime' => $mime, 'bytes' => $png] = app(EditImageAction::class)->execute($fotoLogo, 'Ricrea il logo...');
```

## Configurazione

- Chiavi in `.env`, mai nel repository: `OPENROUTER_API_KEY` (`services.openrouter.key`) e
  `GROQ_API_KEY` (`services.groq.key`). Senza nessuna chiave l'azione fallisce **prima** di chiamare.
- `config/{tenant}/ai.php` (`AIConfigData`):
  - `vision_providers`: ordine dei fornitori, default `['openrouter', 'groq']`. Se il primo fallisce
    (errore HTTP, chiave mancante, risposta non JSON, troppe immagini) si prova il secondo; il
    messaggio finale elenca il motivo di ciascuno.
  - `vision_models`: modello per fornitore. Default OpenRouter `google/gemini-2.5-flash`, Groq
    `qwen/qwen3.8-27b`.
  - `image_model` (solo OpenRouter), `vision_timeout` (120 s).
- Le differenze di protocollo sono in `Enums\AiProviderEnum`: base URL, chiave, limite immagini,
  campi extra (`provider.require_parameters` solo per OpenRouter), intestazioni dell'app.

### Groq (verificato sulla documentazione, 2026-11-17)

- Base URL `https://api.groq.com/openai/v1`, compatibile OpenAI.
- Un solo modello di produzione/anteprima legge immagini: `qwen/qwen3.8-27b`, **preview** ("può essere
  ritirato con breve preavviso"): per questo è il secondo della lista e non il primo.
- Massimo **3 immagini** per richiesta, richiesta con immagini fino a 20 MB, ogni immagine conta
  2048 token: l'azione salta Groq se le immagini sono di più.
- Structured outputs `json_schema` con `strict: true` supportati da `qwen/qwen3.8-27b` e dai GPT-OSS:
  tutti i campi in `required`, `additionalProperties: false`, null per i facoltativi; niente streaming.
- Groq non genera immagini: il logo passa sempre da OpenRouter.

## Scelte, con le fonti

- Testo prima delle immagini nello stesso messaggio utente; immagini private come data URI base64;
  tipi png, jpeg, webp, gif ([image understanding](https://openrouter.ai/docs/guides/overview/multimodal/image-understanding)).
- `response_format` `json_schema` con `strict: true` e `provider.require_parameters: true`: OpenRouter
  instrada solo verso i fornitori che supportano lo schema; la conformità non è garantita ovunque,
  quindi chi usa l'azione **valida** comunque il risultato ([structured outputs](https://openrouter.ai/docs/guides/features/structured-outputs)).
- Immagini: `POST /api/v1/images`, immagine di partenza in `input_references`, risultato in
  `data[].b64_json`; fatturazione solo per immagini completate ([image generation](https://openrouter.ai/docs/guides/overview/multimodal/image-generation)).
- Il system prompt vieta di inventare: ciò che non si legge resta vuoto. Temperatura 0.
- Intestazioni `HTTP-Referer` (`app.url`) e `X-Title` (`app.name`) per riconoscere l'app su OpenRouter.

## Test

`tests/Unit/Vision/VisionActionsTest.php`: tutto con `Http::fake()`, nessuna chiamata reale. Coprono
ordine testo/immagini, data URI, schema strict, errore HTTP con il messaggio del fornitore, chiave
mancante, file non immagine, decodifica dell'immagine modificata.

## Debito noto nel modulo

`Services/AIChatCompletionClient` ha ancora modello (`gpt-4`) e system prompt (ticket della PA)
scritti nel codice: non è agnostico. Le nuove azioni non lo usano; va riallineato.

## Verifica dal vivo

La rete dell'ambiente di sviluppo cloud blocca openrouter.ai e api.groq.com: la prova reale va
fatta sul server con le chiavi in `.env`, ad esempio con una pagina di menu fotografata.

