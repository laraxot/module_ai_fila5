---
title: "Audit qualità modulo AI"
type: story
status: review
module: AI
issue: https://github.com/laraxot/base_techplanner_fila5/issues/64
created: 2026-11-19
updated: 2026-11-19
tags: [audit, qualita, phpstan, pest, docs, dry]
---

# Audit qualità — modulo AI

Il modulo è condiviso da tutti i progetti: le modifiche tolgono solo doppioni senza chiamanti,
nessuna API viva cambia.

## Baseline

- PHPStan `Modules/AI`: 0 errori (prima e dopo).
- Pest `Modules/AI/tests`: 3 falliti / 111 passati → 1 fallito / 113 passati.

## Trovato

- Un merge aveva riportato in vita codice già ritirato in [services-to-actions-ai](./services-to-actions-ai.story.md):
  `app/Services/` (5 file), `app/Support/` (3 file), zero chiamanti in tutto `laravel/`.
- Doppioni senza chiamanti: `Actions/Support/MakeAIRequestAction` e `RequestChatCompletionAction`
  (copie di `Actions/MakeAIRequestAction`), `Actions/Cast/CastScalarTo*Action` (copie dei metodi di
  `ScalarCasterAction`), `Actions/Predict/GetPredictionDraftFallbackTemplatesAction` (copia di
  `Prediction/GetPredictionFallbackTemplatesAction`), `Datas/AIPromptTemplates` (costanti dei Services).
- `Actions/Prompt/AIPromptTemplates` ripeteva il testo delle due `BuildTicket*PromptAction` e faceva fallire
  `QueueableActionContractTest`; idem `BasicSentimentAnalyzer`/`TransformersSentimentAnalyzer` + contratto
  `Contracts/SentimentAnalyzer` (doppioni delle `Analyze*SentimentAction`).
- Due test senza `uses(TestCase)`: girano senza applicazione ("facade root has not been set").

## Fatto

- Rimossi i file sopra (18 classi). `BuildAIPromptAction` ora delega a
  `BuildTicketClassificationPromptAction` / `BuildTicketSolutionsPromptAction`: un solo testo per prompt.
- Ritorno delle `Analyze*SentimentAction` tipizzato `array{label: string, score: int|float, warning: string}`;
  tolto un docblock orfano.
- `uses(TestCase)` in `UserIdColumnsAcceptUuidTest` e `AiActionProposalTest`.
- `mixed` in `app/`: 102 → 65.
- Docs: 13 file con data/timestamp nel nome rinominati o fusi (data nel frontmatter `created:`),
  15 duplicati byte-identici rimossi, `INDEX.md` vuoto rimosso (indice unico: [index.md](../index.md)),
  `00-index.md`/`00-INDEX.md` marcati come storici; `coverage.md` e `PHILOSOPHY.md` allineati al codice.

## Resta

- `UserIdColumnsAcceptUuidTest` fallisce perché il DB di test non ha le tabelle `ai_*` (migrazioni non eseguite:
  dati sacri, nessun `RefreshDatabase`). Ambiente, non codice.
- `PredictionDraftFallbackTemplatesAction` è una terza copia dei template di fallback, usata solo dal suo test:
  da fondere con `GetPredictionFallbackTemplatesAction` insieme al test.
- `GenerateDs4Action`, `ChatDs4Action`, `CreateAiThreadAction`, `BuildAssistantReportAction`, `FineTuning`
  non hanno chiamanti qui ma sono API del modulo condiviso: verificare negli altri progetti prima di toccarle.
