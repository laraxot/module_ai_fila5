---
title: "Immagini con OpenRouter: estrazione strutturata e modifica"
type: guide
status: active
created: 2026-11-16
updated: 2026-11-16
module: AI
qmd: "openrouter vision immagini json schema structured outputs edit image logo agnostico"
---

# Immagini con OpenRouter

Due azioni **agnostiche**: non sanno cosa c'è nelle immagini. Le istruzioni e lo schema li passa il
modulo che le usa (es. Restaurant: foto del menu cartaceo → sezioni e piatti; foto del logo → logo
pulito). Il modulo AI non conosce menu, ristoranti o piatti.

| Azione | Endpoint | Restituisce |
|---|---|---|
| `Actions\Vision\ExtractStructuredDataFromImagesAction` | `{vision_base_url}/chat/completions` | array conforme allo schema JSON passato |
| `Actions\Vision\EditImageAction` | `{vision_base_url}/images` | `['mime' => ..., 'bytes' => ...]` |

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

- Chiave: `.env` `OPENROUTER_API_KEY`, letta da `config('services.openrouter.key')`. Senza chiave le
  azioni lanciano `AiRequestException` **prima** di chiamare il servizio.
- `config/{tenant}/ai.php` (`AIConfigData`): `vision_base_url` (default `https://openrouter.ai/api/v1`),
  `vision_model`, `image_model`, `vision_timeout` (120 s). I modelli di default sono un punto di
  partenza: verificare sul catalogo OpenRouter che il modello di lettura supporti immagini **e**
  structured outputs, e che quello immagini compaia con `output_modalities=image`.

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
