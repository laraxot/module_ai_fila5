---
title: "AI Module — documentation index"
type: index
module: AI
status: active
created: 2026-06-05
updated: 2026-11-27
qmd: "AI module documentation index Laraxot MCP LLM agent assistant prospecting BMAD ECC Second Brain"
tags: [documentation, ai, index, mcp, bmad, ecc]
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/28"
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
related:
  - ../README.md
  - ./wiki/index.md
  - ./github-ops/00-index.md
---

# AI Module — Documentazione canonica

Il modulo AI fornisce **integrazioni LLM, agenti, base di conoscenza e proposte controllate** condivisibili tra applicazioni Laravel/Laraxot. Il dominio host definisce proprie regole, ruoli, clienti e azioni operative. La documentazione è owner-local e deve contenere riferimenti verificabili al branch `dev`.

## Indice verificato nel repository

| Scopo | Documento |
|---|---|
| Visione e requisiti | [PRD agnostico](prd.md) |
| Fasi, priorità e release gate | [Roadmap](roadmap/README.md) |
| Contratti Actions / invarianti | [Contracts](contracts/INDEX.md) |
| Decisioni architetturali | [ADR index](adr/index.md) |
| Agenti, protocollo e permessi tool | [AG-UI/Neuron agents](agents.md) |
| MCP | [MCP](mcp.md) |
| Assistente e base di conoscenza | [Assistant](assistant.md) |
| Spatie Queueable Actions | [Queueable Actions](queueable-actions.md) |
| Ricerca clienti e fonti | [Closer/Sellbird study](prompts/clients.md) |
| Motore prospetti implementativo | [Prospecting](prospecting.md) |
| BMAD story e DoD | [Prospecting epic](bmad/prospecting-story.md) |
| GitHub issues/views/projects/etc | [GitHub operations](github-ops/00-index.md) |
| Second Brain e QMD | [Wiki index](wiki/index.md) · [local discipline](wiki/concepts/second-brain-local-discipline.md) |
| Manuale con catture reali archiviate | [Manuale utente](manuals/manuale-utente.md) |
| Debito operativo | [Next steps](NEXT-STEPS.md) · [Stories](stories/) |

## Confini e convenzioni

- `Actions` del dominio AI in `app/Actions/`, mai in `Modules/Xot/app/Actions/AI/`. `execute()` è il contratto pubblico, con QueueableAction quando richiesta.
- Modelli/migrazioni rispettano le basi Xot; Filament segue la struttura Laraxot. Nessuna dipendenza AI verso dominio Restaurant, Village, TechPlanner o Ticket.
- Per side effects: [ADR-0001](adr/0001-ai-proposes-human-confirms.md) impone approvazione umana + handler host; il debito attuale su replay/race è [#42](https://github.com/laraxot/module_ai_fila5/issues/42).
- Per nuovi prospect: [#32](https://github.com/laraxot/module_ai_fila5/issues/32); sicurezza [#33](https://github.com/laraxot/module_ai_fila5/issues/33), privacy [#36](https://github.com/laraxot/module_ai_fila5/issues/36). Nessun automatic cold outreach.
- Per sorgenti documentali: frontmatter `title,type,module,updated,qmd,issues,related` quando appropriato; fonti e osservazioni separate da inferenze.

## Verifiche in progetto host

```bash
cd laravel
php artisan module:list
php -l Modules/AI/app/Actions/Prospecting/ImportProspectCandidatesAction.php
vendor/bin/pint --test Modules/AI
vendor/bin/phpstan analyse Modules/AI --no-progress
vendor/bin/pest Modules/AI/tests
```

Questi sono **comandi da eseguire**, non test dichiarati superati. Il workflow [Prospecting PHP syntax #5](https://github.com/laraxot/module_ai_fila5/actions/runs/45969852134) risulta positivo per `php -l` sul commit `5f3a72e`; non sostituisce migrazioni, test end-to-end e PHPStan nell'applicazione host.

## Igiene e consolidamento

Nel repository erano presenti link nell'indice a cartelle/documenti non esistenti (es. `docs/mcp/01-installazione-2.md`, `docs/tutorials/`): sono stati rimossi **solo dall'indice**, senza cancellare dati o cambiare pagine legacy. L'iniziativa [#28](https://github.com/laraxot/module_ai_fila5/issues/28) richiede di mantenere la documentazione compatta: aggiornare pagine canoniche prima di creare duplicati. Nessuna percentuale di maturità viene presentata senza criteri osservati e prove riproducibili.
