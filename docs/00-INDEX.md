---
title: "AI: indice operativo"
type: index
status: active
created: 2026-09-26
updated: 2026-10-20
module: AI
tags: [ai, index, docs, ollama, mcp]
qmd: "ai indice operativo ollama ds4 openai mcp piani roadmap proposte azione sentiment ticket"
---

# AI: indice operativo canonico

Percorso: `laravel/Modules/AI/docs/`. Questo e' l'indice canonico del contratto docs. `index.md` e' l'elenco esteso per argomento (del 2026-09-03, con 6 link a file oggi assenti); `00-index.md` e `INDEX.md` sono varianti precedenti. Nessun file e' stato rimosso.

Identita', contenuto reale del codice e limiti: [README.md](./README.md).

## Stato verificato il 2026-10-14

| Tema | Esito | Prova |
|---|---|---|
| Codice MCP nel modulo | assente | `grep -ri mcp` su `app/`, `config/`, `routes/`, `composer.json` del modulo: nessun risultato |
| Server MCP nel progetto | nessuno definito | `laravel/routes/ai.php`: solo commenti |
| Consumatori del modulo | nessuno | nessun `Modules\AI\` fuori da `config/app.php` |
| Rotte | solo pannello Filament `ai/admin` (11) | `php artisan route:list --json` |
| Interfaccia di chat | assente | pagine Filament: `Completion`, `FineTuning`, `Dashboard` |
| Spostamento Ollama da Xot | copia, non spostamento | file ancora in `Modules/Xot/app/Actions/AI/Ollama/` |
| Suite Pest | si ferma | `Cannot redeclare class OpenAI` (un test collide con `openai-php`) |
| PHPStan | 0 errori su `AIService` + test (verify exit 0) | [phpstan-ai-swarm-wave.story.md](./stories/phpstan-ai-swarm-wave.story.md); peer: [phpstan-test-wave-ai.story.md](./stories/phpstan-test-wave-ai.story.md); storico: [phpstan-cleanup-ai](./stories/phpstan-cleanup-ai.story.md), [services-to-actions-ai](./stories/services-to-actions-ai.story.md) |

## Da leggere per primi

- [README.md](./README.md): cosa fornisce e cosa no.
- [decision-log.md](./decision-log.md): vuoto (solo la voce modello).
- [wiki/log.md](./wiki/log.md): registro datato delle verifiche.
- [stories/](./stories/): PHPStan wave: [phpstan-ai-swarm-wave](./stories/phpstan-ai-swarm-wave.story.md); peer pull: [phpstan-test-wave-ai](./stories/phpstan-test-wave-ai.story.md); Actions: [services-to-actions-ai](./stories/services-to-actions-ai.story.md).
- [wiki/concepts/ollama-actions-ownership.md](./wiki/concepts/ollama-actions-ownership.md): dove devono vivere le Actions AI.

## Piani, strategia e visione (non descrivono il codice)

[PRD.md](./PRD.md), [PRODUCT_ROADMAP.md](./PRODUCT_ROADMAP.md), [PRODUCT_STRATEGY.md](./PRODUCT_STRATEGY.md), [PRODUCT_LAUNCH_PLAN.md](./PRODUCT_LAUNCH_PLAN.md), [SPRINT_PLANNING.md](./SPRINT_PLANNING.md), [USER_RESEARCH.md](./USER_RESEARCH.md), [ROADMAP.md](./ROADMAP.md), [NEXT-STEPS.md](./NEXT-STEPS.md), [GSD_WORKFLOW.md](./GSD_WORKFLOW.md), [ollama-strategy.md](./ollama-strategy.md), [ollama-mcp-integration-vision.md](./ollama-mcp-integration-vision.md). Il [deferred-operator-assistant-agent.md](./deferred-operator-assistant-agent.md) riguarda il modulo `AiAssistant` di un altro progetto (`gestionale_commesse`).

## Riferimento tecnico

- Ollama e DS4: [ollama.md](./ollama.md), [ollama-mcp-setup.md](./ollama-mcp-setup.md), [ollama-mcp-usage-guide.md](./ollama-mcp-usage-guide.md): da riverificare contro `app/Actions/Ollama/` e `app/Actions/Ds4/`.
- MCP: cartella [mcp/](./mcp/) (41 file, guide generali; coppie con trattino e underscore), [mcp.md](./mcp.md), [cursor-mcp.md](./cursor-mcp.md), [tutorials/](./tutorials/).
- Previsioni (dominio di un altro progetto): [GENERATE_PREDICTIONS_ACTION.md](./GENERATE_PREDICTIONS_ACTION.md).
- Contratti e dati: [contracts-naming.md](./contracts-naming.md), [database-factories-seeders.md](./database-factories-seeders.md).

## Directory

| Percorso | Ruolo |
|---|---|
| `README.md`, `00-INDEX.md`, `decision-log.md` | canonico |
| `stories/` | cronologia BMAD |
| `wiki/` (46 file) | regole e concetti locali |
| `mcp/`, `tutorials/`, `concepts/`, `sources/` | riferimento |
| `bmad/`, `roadmap/`, `outputs/` | lavoro BMAD e piani |
| `raw/` | evidence immutabile |
| `llm-wiki/` | strumenti |
| 125 file `.md` nella radice | misto, con coppie che differiscono per maiuscole o underscore (32 collisioni); consolidamento in DOC-004 e DOC-006 |
| 27 stub con `canonical: .../Themes/docs/shared-components/...` | il percorso non esiste: la cartella reale e' `Themes/Barthelemy/docs/shared-components/` |

## Riferimenti esterni al modulo

- [Ralph + GSD + BMAD orchestration](../../../../bashscripts/ai/.agents/docs/architecture/ralph-gsd-bmad-orchestration.md) e [indice docs agenti](../../../../bashscripts/ai/.agents/docs/00-INDEX.md): la governance non va duplicata qui.
- [AGENTS.md](../../../../AGENTS.md).
