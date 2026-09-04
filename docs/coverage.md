# AI module — coverage / quality log

## 2026-09-04 — mixed type reduction

**Scope**: reduce use of the PHP `mixed` type where a more specific type is actually knowable, per project convention ("cerchiamo di non usare mixed, quando lo troviamo cerchiamo di sostituirlo con qualcosa di adeguato"). Best-effort, not 100% coverage.

**Found**: 74 `\bmixed\b` occurrences across 33 `.php` files (one occurrence, in `tests/Unit/Actions/SentimentActionTest.php`, was a false positive — the word "mixed" inside test fixture prose "I have mixed feelings", not a type usage — so 73 real occurrences across 32 files).

**Changed**: 6 occurrences across 6 files, all docblock-only (no native `mixed` type-hints were touched — see "left as mixed" below for why the native ones stay). Replaced `array<string, mixed>` / `array<int, array<string, mixed>>` with a concrete shape once the returned/consumed structure was evidently stable:

- `Modules/AI/app/Contracts/SentimentAnalyzer.php` — `analyze()` return: `array{label: string, score: int|float, warning: string}`.
- `Modules/AI/app/Actions/Sentiment/BasicSentimentAnalyzer.php` — same shape (implements the contract).
- `Modules/AI/app/Actions/Sentiment/TransformersSentimentAnalyzer.php` — same shape.
- `Modules/AI/app/Actions/Sentiment/AnalyzeBasicSentimentAction.php` — same shape.
- `Modules/AI/app/Actions/Sentiment/AnalyzeTransformersSentimentAction.php` — same shape.
- `Modules/AI/app/Actions/Prediction/GetPredictionFallbackTemplatesAction.php` — `execute()` return tightened from `array<int, array<string, mixed>>` to `array<int, array{category: string, title: string, subtitle: string, description: string, analysis: string, tags: list<string>, options: list<string>}>` (hardcoded literal templates, shape evident and stable; this also benefits the one consumer, `GeneratePredictionDraftsAction::fallbackDrafts()`).

**Left as `mixed`, with reason** (~67 occurrences, ~26 files):

- `app/Actions/Cast/{CastScalarToStringAction,CastScalarToNullableStringAction,CastScalarToStringListAction,ScalarCasterAction}.php` (10 occurrences) — these are the module's own generic scalar-casting utilities; the whole point of `execute(mixed $value, ...)` is to accept any caller-provided value and normalize it. Native `mixed` here is intentional and correct, not a typing gap.
- `app/Models/{AiMessage,AiThread,AiActionProposal,AiToolLog}.php` (6 occurrences) — `@property array<string, mixed>|null` on JSON columns (`payload`, `meta`, `arguments`, `response`). Genuinely polymorphic JSON storage; no evidence of a stable schema to narrow to.
- `database/factories/{AiActionProposalFactory,AiMessageFactory,AiThreadFactory,AiToolLogFactory}.php` (4 occurrences) — standard Laravel `definition(): array<string, mixed>` convention; the returned array legitimately mixes string/int/bool/Carbon column values.
- `app/Actions/{GeneratePredictionsAction,GeneratePredictionDraftsAction,Prompt/BuildAIPromptAction,Support/MakeAIRequestAction,Support/RequestChatCompletionAction,AiJsonResponseDecoderAction,ClassifyTicketAction,SuggestSolutionsAction,ContextCompressorAction}.php`, `app/Datas/{PredictionData,OpenAiPredictionMapper}.php`, `app/Contracts/AiActionHandlerContract.php`, `app/Filament/Pages/{Completion,FineTuning}.php`, `tests/Support/OpenAiHttpFake.php`, `app/Actions/CreateAiActionProposalAction.php` — externally-sourced JSON (LLM responses, HTTP `Http::json()`/`json_decode()` results, Filament form state, caller-supplied `array<string, mixed> $options/$params/$context/$data`, HTTP request bodies). Values are read defensively via `is_string`/`is_scalar`/`is_numeric`/`is_array` checks before use; the shape is caller-defined and not stable enough to narrow honestly. Narrowing these would either be dishonest (an `@var` override not backed by the runtime) or would just move the `mixed` one level down (e.g. `array<array-key, mixed>`), so left as-is per "genuinely polymorphic JSON/config payloads" guidance.

**PHPStan**: `./vendor/bin/phpstan analyse Modules/AI --no-progress --error-format=table` → **0 errors before, 0 errors after** (baseline measured before any edit).

**PHPMD**: `./tools/phpmd.sh Modules/AI text ../docs/phpmd.ruleset.xml` ran cleanly (no crash). Findings are pre-existing debt unrelated to this diff (`CyclomaticComplexity` in `ContextCompressorAction`/`ChatDs4Action`/`GeneratePredictionDraftsAction`, `MissingImport` in `Ds4`/`Ollama` actions, `LongClassName`/`LongVariable`) — none of the touched files/lines are flagged except a pre-existing `LongVariable` on `AnalyzeTransformersSentimentAction.php:17` (constructor property name, untouched by this diff).

**Pest**: `Modules/AI/phpunit.xml` does not exist in this module, so the module-scoped Pest invocation (`./vendor/bin/pest Modules/AI/tests -c Modules/AI/phpunit.xml --no-coverage`) is not runnable as specified. Not attempted further (see task budget/instructions — one attempt only).

**Story**: `Modules/AI/docs/stories/ai-mixed-type-reduction.story.md`.
