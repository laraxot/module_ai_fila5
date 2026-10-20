---
title: PHPStan AI, servizio e test riapparsi col pull
type: story
module: AI
phase: implementazione, quality gate
status: done
created: 2026-10-20
updated: 2026-10-20
tags: [phpstan, test, pull, ai]
related:
  - ../../../Xot/docs/stories/phpstan-test-wave-regression.story.md
  - services-to-actions-ai.story.md
  - phpstan-cleanup-ai.story.md
---

# PHPStan AI: servizio e test riapparsi col pull

Lane `gdpr-lang-ai` del run PHPStan del 2026-10-20. Coordinamento, regole di lane e scala delle decisioni: [phpstan-test-wave-regression.story.md](../../../Xot/docs/stories/phpstan-test-wave-regression.story.md).

## Misura

- Baseline delle 09:00: 33 errori in 2 file. `app/Services/AIService.php` 31 (24 `missingType.iterableValue`, 7 `method.unused`), `tests/Unit/Services/AIServiceTest.php` 2 (`class.notFound`).
- Dopo: 0 errori su entrambi (PHPStan mirato, 2026-10-20 circa 09:58).

## Chi ha scritto cosa

Questa lane non ha modificato nessun file di AI. Quando e' arrivata ai due file, `AIService.php` e `AIServiceTest.php` erano gia' sotto lock di un'altra lane (`Agent: lane-ai`, `Task: phpstan-wave-ai`, lock delle 09:48:21 su entrambi). Per la regola del gate (lock attivo, file modificato da meno di due minuti) sono stati lasciati a lei. Le righe seguenti descrivono la causa che questa lane ha letto nel baseline e nel diff gia' presente nel working tree, non una modifica fatta qui. I file `.lock` non sono stati rimossi.

## Scopo capito

`AIService` e' il vecchio servizio che per un ticket costruisce un prompt, chiede al modello una risposta JSON (messa in cache con le stesse chiavi `ai:*` di `ClassifyTicketAction` e `SuggestSolutionsAction`) e la decodifica con i metodi `parse*Response()`. Il JSON e' output del modello, non validato: i risultati sono `array<string, mixed>`, con il valore di default del flusso quando la risposta non e' un oggetto JSON.

## Causa

Il commit `0c5e0a4` del repo del modulo (2026-10-20 08:57, sync del pull) ha riportato `app/Services/AIService.php`, `app/Contracts/SentimentAnalyzer.php` e `tests/Unit/Services/AIServiceTest.php`, cancellati l'8 ottobre dal commit `dfed680`.

- Sette metodi `parse*Response()` erano privati e mai chiamati (`method.unused`): i flussi pubblici non passavano dai `parse*Response()`, quindi la decodifica scritta per loro non partiva mai.
- 24 `missingType.iterableValue`: firme con `array` senza tipo dei valori.
- Il test importava `Modules\AI\App\Services\AIService` (namespace con `App` maiuscolo), che non esiste; il namespace reale e' `Modules\AI\Services`. Era una classe PHPUnit con `TestCase` globale `Tests\TestCase` e un `@phpstan-ignore-next-line`.

## Correzione presente nel working tree (lane-ai)

- I flussi cacheano e rileggono una stringa JSON e la passano al `parse*Response()` corrispondente (il service ora importa `AiJsonResponseDecoderAction`), con `@return array<string, mixed>` e `list<array<string, mixed>>` per i parametri che finiscono serializzati nel prompt.
- Il test e' diventato Pest con `Http::fake` su tre flussi (classificazione, routing, sentiment con risposta non JSON), namespace corretto, niente `@phpstan-ignore`.

## Decisione aperta

`services-to-actions-ai.story.md` (2026-10-08) aveva eliminato `AIService` perche' senza chiamanti: `rg` su `Modules` e `Themes` non trova oggi nessun uso di produzione, solo il test. La riparazione sul posto, come indicato dal coordinamento, e' stata applicata; la conversione in QueueableAction non e' stata fatta perche' i chiamanti sono zero (sotto la soglia di circa 3). Resta da decidere se `AIService` debba tornare cancellato (`git show dfed680^:app/Services/AIService.php`), con un doppione in meno e la logica dei prompt in `BuildAIPromptAction`.
