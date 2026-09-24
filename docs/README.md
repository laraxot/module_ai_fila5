---
title: "AI Module Documentation"
type: documentation
tags: [module, documentation]
created: 2026-06-05
updated: 2026-07-24
---

# 🤖 AI Module - Integrazione MCP

## Ownership Actions (obbligatorio)

Le Actions di dominio AI (Ollama, compression, predictions) vivono in
`app/Actions/` di **questo** modulo — mai in `Modules/Xot/app/Actions/AI/`.

| Doc | Link |
|-----|------|
| Wiki ownership | [wiki/concepts/ollama-actions-ownership.md](wiki/concepts/ollama-actions-ownership.md) |
| Move 2026-07-24 | [ollama-actions-moved-from-xot.md](ollama-actions-moved-from-xot.md) |
| Root rule | [domain-actions-belong-to-domain-module](../../../../bashscripts/ai/wiki/rules/domain-actions-belong-to-domain-module.md) |

## 📋 Quick Reference
| Categoria | Guida | File |
|-----------|-------| ---- |
| **Setup** | MCP Server Setup | [mcp/01-installazione.md](mcp/01-installazione.md) |
| **Config** | Configurazione | [mcp/02-configurazione.md](mcp/02-configurazione.md) |
| **Usage** | Utilizzo pratico | [mcp/03-utilizzo.md](mcp/03-utilizzo.md) |
| **Integration** | Chat System | [chat.md](chat.md) |
| **Training** | Fine Tuning | [fine-tuning.md](fine-tuning.md) |
| **Tools** | Ollama, CLI Tools | [ollama.md](ollama.md), [tools.md](tools.md) |
| **Troubleshooting** | Errori comuni | [mcp/06-troubleshooting.md](mcp/06-troubleshooting.md) |

## 🎯 Core Features
- **MCP Protocol**: Database integration, external services, custom tools
- **AI Chat**: Multi-user interface, context management, memory persistence
- **Model Training**: Fine tuning, data preparation, deployment

## 📁 Documentation Structure
- `/mcp/` - Documentazione completa MCP (00-11)
- `/tutorials/` - Tutorial pratici Laravel+MCP
- `/phpstan/` - Configurazioni PHPStan

## Panoramica

Il modulo AI fornisce funzionalità di integrazione con il Model Context Protocol (MCP) per consentire alle applicazioni Laravel di comunicare efficacemente con modelli di linguaggio (LLM) e implementare agenti AI avanzati.

## Documentazione

Questa directory contiene la documentazione completa sull'integrazione MCP in Laravel, con particolare attenzione ai casi d'uso, all'implementazione e alle best practices.

### Indice della Documentazione

La guida MCP completa e numerata vive in [mcp/00-indice.md](./mcp/00-indice.md) (00-11). Punti di ingresso equivalenti ai tre argomenti sotto:

1. [Introduzione a MCP](./mcp/00-introduzione.md) — panoramica del protocollo, vantaggi per Laravel, architettura e componenti principali.
2. [Utilizzo pratico](./mcp/03-utilizzo.md) — casi d'uso, automazione, assistenza utente, analisi dati.
3. [Implementazione pratica](./mcp/05-implementazione-pratica.md) — sviluppo di tool MCP, testing, debugging.

## Tutorial e riferimenti esterni

I riferimenti a documentazione MCP esterna (SDK ufficiale, Neuron AI, implementazioni open source come InnoGE/laravel-mcp e OPGG/laravel-mcp-server) che qui erano linkati sotto `docs/project/references/` non esistono piu' in questo repo: quella cartella e' stata rimossa in una ristrutturazione precedente. I tutorial pratici equivalenti restano in [tutorials/](./tutorials/) (vedi [index.md](./index.md#tutorial) per l'elenco).

## Contribuire

Se desideri contribuire a questa documentazione o all'implementazione del modulo AI, segui le linee guida del progetto e assicurati che il codice sia conforme agli standard PHPStan del modulo (vedi [phpstan-status.md](./phpstan-status.md)).

---

> ℹ️ **Per l'installazione e la gestione centralizzata degli MCP servers, consulta la guida [installazione-mcp-servers.md](./installazione-mcp-servers.md).**

---

*Ultimo aggiornamento: Maggio 2025*
*Principio DRY: Una funzionalità = Una documentazione. Collegamenti logici e struttura pulita.*

## AI Workflows
- [AI Methodologies](./ai-methodologies.md)
- [OpenViking setup (bashscripts/ai)](../../../../bashscripts/ai/.agents/docs/openviking-setup.md)

## LLM Wiki Workflow

- Local compiled wiki: [./wiki/README.md](./wiki/README.md)
- Local compiled index: [./wiki/index.md](./wiki/index.md)
- Project compiled index: [../../../../docs/wiki/index.md](../../../../docs/wiki/index.md)
- [AI LLM Wiki](./llm-wiki.md)


## Standard Rules & Workflow

- [BMAD Method (local)](./wiki/bmad-method.md)
- [Context Engineering](../../../../bashscripts/ai/wiki/concepts/context-engineering.md)
- [LLM Wiki Governance](../../../../bashscripts/ai/wiki/concepts/llm-wiki-governance.md)

## Documentation

- [On-Demand Pattern](./ON-DEMAND-PATTERN.md) — Pattern per caricamento efficiente
- [QMD Setup](./QMD-SETUP.md) — Configurazione ricerca locale
- [Performance](./PERFORMANCE-OPTIMIZATION.md) — Metriche e best practice
- [Project Structure](./PROJECT-STRUCTURE.md) — Directory layout
