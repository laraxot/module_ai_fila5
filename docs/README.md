---
title: "AI Module Documentation"
type: documentation
tags: [module, documentation]
created: 2026-06-05
updated: 2026-09-25
---

# 🤖 AI Module - Integrazione MCP

## 📋 Quick Reference
| Categoria | Guida | File |
|-----------|-------| ---- |
| **Setup** | MCP Server Setup | [mcp/01_installazione.md](mcp/01_installazione.md) |
| **Config** | Configurazione | [mcp/02_configurazione.md](mcp/02_configurazione.md) |
| **Usage** | Utilizzo pratico | [mcp/03_utilizzo.md](mcp/03_utilizzo.md) |
| **Integration** | Chat System | [chat.md](chat.md) |
| **Training** | Fine Tuning | [fine_tuning.md](fine_tuning.md) |
| **Tools** | Ollama, CLI Tools | [ollama.md](ollama.md), [tools.md](tools.md) |
| **Troubleshooting** | Errori comuni | [mcp/06_troubleshooting.md](mcp/06_troubleshooting.md) |

## 🎯 Core Features
- **MCP Protocol**: Database integration, external services, custom tools
- **AI Chat**: Multi-user interface, context management, memory persistence
- **Model Training**: Fine tuning, data preparation, deployment

## 📁 Documentation Structure
- `/mcp/` - Documentazione completa MCP (00-11)
- `/tutorials/` - Tutorial pratici Laravel+MCP
- `/phpstan/` - Configurazioni PHPStan

## Contracts and cross-links

- [Dashboard page contract](./wiki/concepts/dashboard-page-contract.md) — the AI panel landing page extends `XotBaseDashboard`, not `XotBasePage`.
- [Module dashboard rule](../../../Themes/docs/shared-components/module-dashboard-page-religion.md) — shared panel boundary and discovery contract.
- [BMAD docs hygiene story](../../../../docs/stories/2.4.module-theme-docs-hygiene.story.md) — campaign owner and evidence trail.
- [Module README](../README.md) — product-facing module entry point.

## Panoramica

Il modulo AI fornisce funzionalità di integrazione con il Model Context Protocol (MCP) per consentire alle applicazioni Laravel di comunicare efficacemente con modelli di linguaggio (LLM) e implementare agenti AI avanzati.

## Documentazione

Questa directory contiene la documentazione completa sull'integrazione MCP in Laravel, con particolare attenzione ai casi d'uso, all'implementazione e alle best practices.

### Indice della Documentazione

1. [Guida all'Integrazione MCP](./mcp/mcp-integration-overview.md)
   - Panoramica completa del protocollo MCP
   - Vantaggi dell'integrazione in Laravel
   - Architettura e componenti principali
   - Casi d'uso generali e implementazione base

2. [Casi d'Uso di MCP in Laravel](./mcp/utilizzo.md)
   - Esempi dettagliati di casi d'uso specifici
   - Automazione dei processi di business
   - Assistenza utente e supporto
   - Analisi e gestione dei dati
   - Sviluppo e debugging

3. [Implementazione MCP in Laravel](./mcp/05-implementazione-pratica.md)
   - Guida dettagliata all'implementazione
   - Sviluppo di strumenti MCP
   - Integrazione con l'architettura esistente
   - Testing e debugging
   - Conformità con PHPStan Livello 9

## Risorse e riferimenti locali

Le vecchie reference esterne non sono presenti in questo checkout; usare i
documenti owner verificati e non ricreare target placeholder:

- [AI MCP governance](./wiki/concepts/ai-mcp-governance.md)
- [Laravel Boost MCP server](../../../../docs/wiki/concepts/laravel-boost-mcp-server.md)
- [AI agents in PHP with MCP](./tutorials/ai-agents-in-php-with-mcp.md)
- [Laravel helper tools for MCP](./tutorials/laravel-helper-tools-for-mcp.md)
- [MCP integration overview](./mcp/mcp-integration-overview.md)

## Implementazioni Open Source

Prima di aggiungere un link a un progetto esterno, verificare URL, licenza e
versione nella fonte upstream. Il README non mantiene più reference locali
morte per `docs/project/references/`.

## Contribuire

Se desideri contribuire a questa documentazione o all'implementazione del modulo AI, segui le linee guida del progetto e assicurati che il codice sia conforme agli standard di PHPStan livello 9.

---

> ℹ️ **Per l'installazione e la gestione centralizzata degli MCP servers, consulta la guida [installazione-mcp-servers.md](./installazione-mcp-servers.md).**

🔗 **Guida installazione MCP servers:** [installazione-mcp-servers.md](./installazione-mcp-servers.md)

---

*Ultimo aggiornamento: 2026-09-25*
*Principio DRY: Una funzionalità = Una documentazione. Collegamenti logici e struttura pulita.*

## AI Workflows
- [AI Methodologies](./ai-methodologies.md)
- [OpenViking Integration Guide](../../Notify/docs/openviking-integration.md)

## LLM Wiki Workflow

- Local compiled wiki: [./wiki/index.md](./wiki/index.md)
- Project wiki layer: [../../../../docs/wiki/index.md](../../../../docs/wiki/index.md)
- Module wiki guide: [../../../../docs/wiki/how-to/module-wiki-documentation.md](../../../../docs/wiki/how-to/module-wiki-documentation.md)
- Second-brain model: [../../../../docs/wiki/concepts/second-brain-operating-model.md](../../../../docs/wiki/concepts/second-brain-operating-model.md)
- [AI LLM Wiki](./llm-wiki.md)


## Standard Rules & Workflow

- [BMAD Method](../../../../docs/wiki/concepts/bmad-method.md)
- [Context Engineering](../../../../docs/wiki/concepts/context-engineering.md)
- [LLM Wiki Governance](../../../../docs/wiki/concepts/llm-wiki-governance.md)

## Documentation

- [On-Demand Pattern](./on-demand-pattern.md) — Pattern per caricamento efficiente
- [QMD Setup](./qmd-setup.md) — Configurazione ricerca locale
- [Performance](./performance-optimization.md) — Metriche e best practice
- [Project Structure](./project-structure.md) — Directory layout