# References — fonti di pattern per il prospecting

Il motore prospecting (issue #32) è ispirato a due progetti open source studiati
come **fonti di pattern**, non come codice da copiare.

## closer-skill (MIT)

Skill bundle di vendita (11 skill: prospector, researcher, writer, …).

- Licenza **MIT** → riuso di idee e pattern ampiamente consentito.
- Mappato in `docs/prospecting.md` (tabella study map):
  - prospector audit/rank/draft → `DetectWebsiteSignalsAction` + rank
  - researcher brief → `BuildProspectBriefAction`
  - stop/suppression → `ApplyProspectSuppressionAction` + `requires_approval`
  - pipeline board → `NormalizeProspectImportAction` + rank
- Copertura delle 11 skill in adapter agnostici: issue **#35** (P2, M3).

## sellbird (AGPL-3.0)

Piattaforma open-source di outreach B2B (stages discover→…→forecast).

- Licenza **AGPL-3.0** → **solo idee**: nessuna copia di codice, nessun
  derivato, niente endpoint/API AGPL richiamati dal modulo.
- Contributi possibili: pattern di pipeline, staged model, deliverability
  gates (issue #43), inbound/CRM handoff (issue #34).
- Repository di riferimento locale: `/tmp/sellbird` (clone di studio).

## Regola d'oro

| Fonte | Licenza | Uso consentito |
|---|---|---|
| closer-skill | MIT | pattern + attribuzione |
| sellbird | AGPL-3.0 | **solo** ispirazione concettuale, zero codice |

Decisioni e sintesi dello studio: discussion **#44**, issue **#35**.

## Altri riferimenti nel repo

- `docs/prospecting.md` — study map ufficiale Closer/Sellbird → Actions
- `docs/bmad/prospecting-story.md` — story BMAD dello slice
- `docs/wiki/` — wiki LLM con concetti architetturali (Ollama, MCP governance)
- `docs/agents.md` — protocollo AG-UI da datomatic/laravelday-2026 (MIT)
