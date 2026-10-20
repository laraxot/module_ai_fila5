---
title: "AI — Indice canonico docs"
status: canonical
module: AI
updated: 2026-10-20
---

> Indice canonico. Indici storici alternativi: `00-INDEX.md`, `00-index.md`, `index.md`, `INDEX.md`, `README.md`. Nessun file storico cancellato.

> Convenzione: [Docs Organization — Modules & Themes](../../Xot/docs/conventions/docs-organization.md)

# AI — Indice docs

Modulo AI (assistenza / LLM integration).

## Panoramica
- [README.md](./README.md) — presentazione
- [SCOPOM.md / scopo.md](./scopo.md) — scopo modulo
- [STRATEGY.md / strategy.md](./strategy.md) — strategia
- [SUSTAINABILITY-AND-FUNDING.md](./sustainability-and-funding.md)

## Architettura
- [project-structure.md / module.md / structure.md](./project-structure.md) — struttura e architettura
- [REPOSITORIES.md / repositories.md](./repositories.md) — repository
- [CONTRACTS-NAMING.md / contracts-naming.md](./contracts-naming.md) — contratti
- [ON-DEMAND-PATTERN.md](./on-demand-pattern.md) — pattern on-demand
- [ROADMAP.md / ROADMAP.md / roadmap.md](./roadmap.md) — roadmap e stati

## PHPStan
- [phpstan fixes / reports](./) — `redudancy-audit.md`, `code-quality` report
- [REDUNDANCY_ANALYSIS.md](./REDUNDANCY_ANALYSIS.md) — analisi ridondanza

## BMAD / Stories
- [bmad/](./bmad/) — dossier BMAD (decision log, tech spec, architecture, prd)
- [stories/](./stories/) — storie feature (es. `ai-module-bmad-setup.story.md`)
- [stories/phpstan-ai-fix.md](./stories/phpstan-ai-fix.md) — correggimenti PHPStan

## Testing
- [COVERAGE.md / coverage.md / coverage-pest-80.md](./coverage.md) — copertura
- [DATABASE-FACTORIES-SEEDERS.md](./database-factories-seeders.md) — factory e seeder

## API / Guide
- [MCP.md / mcp/](./mcp.md) — integrazione MCP
- [MCP-INSTALLATION.md / installazione-mcp-servers.md](./installazione-mcp-servers.md) — setup server MCP
- [TUTORIALS / tutorials/](./tutorials/) — tutorial (es. `ai-agents-in-php-with-mcp.md`)
- [SOURCES / sources/](./sources/) — fonti e riferimenti
- [WIKI / wiki/](./wiki/) — pagine wiki (es. `index.md`, `commands/`, `concepts/`)

## Struttura
- `bmad/` — dossier BMAD (11 file: architecture, decision-log, epics, livewire-widget-*)
- `stories/` — storie feature (21 file con `phpstan-*` e `ai-*`)
- `concepts/` — concetti architetturali AI
- `wiki/` — wiki con `commands/`, `concepts/`, `memories/`, `rules/`, `skills/`, `troubleshooting/`
- `mcp/` — documentazione MCP
- `llm-wiki/` — materiale LLM wiki
- `raw/` — importazioni storiche (`index.md`, `README.md`, `root-import/`)
- `tutorials/` — guide operative (es. MCP, portfolio API)
- `sources/` — fonti dati
- `outputs/`, `roadmap/` — output e pianificazione

---
*Aggiornato: 2026-10-20 — conforme a `docs-organization.md`; `00-INDEX.md` e `index.md` mantenuti.
