# AI Module Architecture

LLM bridge: provider-agnostic inference, prompts, completions. Foundation for all AI capabilities.

## Design

**Scope:** Standardize LLM access across modules. Decouple from OpenAI/DeepSeek/Ollama implementation.

**Contracts:**
- `AiActionHandlerContract` — action execution interface
- `SentimentAnalyzer` — sentiment classification abstraction

**Data Objects:**
- `CompletionData` — LLM response with metadata
- `PredictionData` — prediction result (used by AiAssistant)
- `AIPromptTemplates` — versioned prompt registry

**Actions:**
- `CompletionAction` — raw inference (minimal)
- `AiJsonResponseDecoderAction` — structured output parsing
- `ContextCompressorAction` — token budget compression
- `SuggestSolutionsAction` — solution generation
- `ClassifyTicketAction` — ticket classification
- `SentimentAction` — sentiment scoring

## Dependencies

```
AI (this module)
├── openai-php/laravel (external)
├── Xot (base classes)
└── Tenant (multi-tenant context)
```

**Zero coupling to domain modules.** AI is infra-only.

## Integration Pattern

Modules do **not** import AI directly. Instead:

1. **Via Contract**: `AiActionHandlerContract` for custom handlers
2. **Via Event**: Domain modules emit events; AiAssistant listens and calls AI
3. **Via Service Provider**: Register handlers in `AIServiceProvider::register()`

```php
// WRONG: direct import
use Modules\AI\Actions\CompletionAction;

// RIGHT: use handler contract
$handler = app(AiActionHandlerRegistry::class)->get('quotation-draft');
$handler->handle($request);
```

**Why:** Loose coupling. AI can be swapped without touching domain code.

## Known Issues & Debt

1. **Incomplete handler registry**: `AiActionHandlerRegistry` has limited handlers. Expand for common patterns (summarize, extract, classify, translate).

2. **No LLM provider abstraction**: Uses OpenAI directly. Should abstract to `LlmProviderContract` (OpenAI, DeepSeek, Ollama compatible).

3. **Missing prompt versioning**: Prompts hardcoded. Should be versioned in `AIPromptTemplates` with fallbacks.

4. **No token budget enforcement**: `ContextCompressorAction` exists but not enforced. Should auto-compress if input > model's context window.

5. **Duplication with AiAssistant**: AiAssistant implements parallel LLM orchestration (Groq, OpenAI direct calls). Should consolidate into AI module's handler system.

## Refactoring: Consolidate LLM Access

**Problem:** AiAssistant talks directly to OpenAI/Groq, bypassing AI module's handler abstraction.

**Solution:**
1. Extract domain-specific handlers from AiAssistant into AI as `Domain\*Handler` classes
   - `Domain\QuotationDraftHandler` (in AI)
   - `Domain\InterventionReportHandler` (in AI)
   - `Domain\SpeechTranscriptionHandler` (in AI)

2. Refactor AiAssistant to use `AiActionHandlerContract` instead of direct LLM calls

3. Register all handlers in AI's `AIServiceProvider`

**Impact:**
- AiAssistant shrinks (thin orchestration layer only)
- AI becomes the single source of truth for LLM patterns
- New domain modules can reuse handlers without reimplementing

## Architecture Decisions

| Decision | Rationale |
|----------|-----------|
| Contracts over classes | Handler interface is stable; implementation swappable |
| Infra-only scope | AI must not depend on Quotation, Intervention, etc. |
| Event-driven integration | Domains emit events; AI listens; no circular imports |
| Provider-agnostic | Support OpenAI, DeepSeek, Ollama via single handler |

## See Also

- `PHILOSOPHY.md` — AI principles and constraints
- `TESTING.md` — testing LLM interactions (mocking completions)
- `docs/handler-registry.md` — implementing custom handlers
- `docs/prompt-versioning.md` — managing prompt templates
- `docs/consolidation-with-aiassistant.md` — consolidation roadmap
