---
title: "AI Module — roadmap verificabile"
type: roadmap
module: AI
status: living
created: 2026-09-26
updated: 2026-11-27
qmd: "AI Laravel module roadmap BMAD ECC prospecting assistant agents quality"
tags: [roadmap, bmad, ecc, milestones]
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
related:
  - ../prd.md
  - ../github-ops/00-index.md
  - ../bmad/prospecting-story.md
---

# AI Module Roadmap

> Le vecchie percentuali (“50% complete”, “60% NLP”, ecc.) non erano collegate a test o criteri osservabili: sono state rimosse. La roadmap usa gate verificabili, non stime di completamento.

## Track condivisi

| Track | Stato osservabile | Prossimo gate |
|---|---|---|
| Provider/LLM, Action, Vision | Classi e test presenti nel repo | Verificare qualità/config e compatibilità host; no percentuali inventate |
| Assistant knowledge / human handoff | `AiKnowledgeEntry`, Actions e resource Filament presenti | Test su host e policy/accesso/tenancy |
| Agents/AG-UI/MCP | `XotBaseAgent`, stream e adapter presenti | Test end-to-end, permessi tool, approvazione e logging |
| Human-confirmed proposals | `AiActionProposal`, handler registry, ADR-0001 presenti | Fix replay/race server-side documentato nell'ADR |
| B2B prospecting | Prima slice su `dev`: import CSV/OSM export, dati business, hint, score, draft, DNC; URL fetch off | Sicurezza, compliance, migrations, UI, host tests |

## Prospecting — ordinamento BMAD/ECC

1. **M0 / Trust**: [#33 SSRF](https://github.com/laraxot/module_ai_fila5/issues/33), [#36 Privacy](https://github.com/laraxot/module_ai_fila5/issues/36), [#42 Approval replay](https://github.com/laraxot/module_ai_fila5/issues/42). Zero automatic outbound.
2. **M1 / Source & identity**: [#40 Data](https://github.com/laraxot/module_ai_fila5/issues/40), [#39 Import](https://github.com/laraxot/module_ai_fila5/issues/39). Provider license, cost, provenance, workspace, stable keys.
3. **M2 / Insight & human review**: [#37 Signals](https://github.com/laraxot/module_ai_fila5/issues/37), [#38 Filament](https://github.com/laraxot/module_ai_fila5/issues/38), [#34 Inbound](https://github.com/laraxot/module_ai_fila5/issues/34). Host owns CRM and offers.
4. **M3 / Quality**: [#41 ECC](https://github.com/laraxot/module_ai_fila5/issues/41). Pest, PHPStan/Pint, migrations, browser access tests, security; runnable evidence.
5. **M4 / Sales operations optional**: [#35 Closer 11 skills](https://github.com/laraxot/module_ai_fila5/issues/35), feature-by-feature after privacy/product pilot.

Milestones M0–M3 sono **proposte in documentazione**; non esistono ancora come milestone GitHub create. Non assegnare date arbitrarie. [Configurazione GitHub](../github-ops/00-index.md).

## Definition of Done

Una story è completata solo quando: acceptance misurabili verificate, rischi P0 risolti, commit e test environment citati, nessun dato personale reale nei test, documenti e issue aggiornati, autorizzazioni tenant/regolatorie provate dove applicabili. La semplice presenza di un file o di un workflow sintattico non basta.

## Riferimenti

- [PRD canonico](../prd.md)
- [Prospecting study e requirements](../prompts/clients.md)
- [Epic and story map](../bmad/prospecting-story.md)
- [GitHub operation surfaces](../github-ops/00-index.md)
