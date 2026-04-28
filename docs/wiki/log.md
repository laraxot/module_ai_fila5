# AI Wiki Log

## [2026-04-15] init | wiki bootstrap
- Added schema, index, and module adoption guide.

## [2026-04-22] concept | OpenRouter context-compression plugin
- **Problema**: BMAD `create-story` fallisce con HTTP 400 quando il contesto supera 131072 token
- **Fix**: abilitare plugin `context-compression` su OpenRouter (globale in settings o via `plugins` in API request)
- **Config**: aggiunto `provider.openrouter.options.plugins` in `.agents/config.json`
- **Concept**: `concepts/openrouter-context-compression.md`

## [2026-04-15] ingest | compile first AI wiki pages
- sources:
  - `laravel/Modules/AI/docs/README.md`
  - `laravel/Modules/AI/docs/structure.md`
  - `laravel/Modules/AI/docs/mcp.md`
  - `laravel/Modules/AI/docs/mcp/mcp-integration-overview.md`
  - `laravel/Modules/AI/docs/ollama-strategy.md`
  - `laravel/Modules/AI/docs/tools.md`
- pages:
  - `laravel/Modules/AI/docs/wiki/overviews/ai-module.md`
  - `laravel/Modules/AI/docs/wiki/concepts/ai-mcp-governance.md`
  - `laravel/Modules/AI/docs/wiki/concepts/local-first-ollama-strategy.md`
- summary:
  - compiled the first durable AI module syntheses from MCP and local-first runtime docs
  - aligned the module wiki with the project-level LLM wiki model
