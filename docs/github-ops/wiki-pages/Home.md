# laraxot/module_ai_fila5 — Wiki

Modulo **AI** dell'ecosistema Laraxot: integrazioni LLM (Ollama locale, OpenRouter, Groq), agenti con tool via protocollo **AG-UI**, assistente clienti e — dal 2026-11 — **motore di prospecting B2B** (epic issue #32).

Repo: https://github.com/laraxot/module_ai_fila5 · branch default: `dev` · repo pubblico.

## Second brain (fonte primaria)

La documentazione operativa vive nel repo, non qui:

- `docs/` — indice completo: `docs/index.md`
- `docs/prospecting.md` — pipeline B2B prospecting (issue #32)
- `docs/bmad/prospecting-story.md` — story BMAD dello slice shipped
- `docs/roadmap/README.md` — roadmap AI toolkit
- `docs/agents.md` — agenti neuron-ai e protocollo AG-UI
- `docs/github-ops/00-index.md` — mappa delle superfici GitHub (issue/label/milestone/discussion)
- `docs/wiki/` — wiki LLM del modulo (Karpathy pattern, frontmatter obbligatorio)

## Superfici chiave del codice

| Area | Percorso |
|---|---|
| Actions | `app/Actions/` (Prospect/, Agent/, Assistant/, Predict/, …) |
| Modelli | `app/Models/Ai*.php` (XotBaseModel) |
| Enum | `app/Enums/` (status prospect, segnali, soppressione, proposte) |
| Filament | `app/Filament/Resources/` — solo XotBase/LangBase, mai Filament diretto |
| Console | `app/Console/Commands/ProspectAuditCommand.php` (`ai:prospect-audit`) |
| Neuron/AG-UI | `app/Neuron/`, `Actions/Agent/StreamAgentRunAction` |
| Test | `tests/Unit` (Pest, `it()`, SQLite `.env.testing`) |

## Pagina della wiki

- [[Prospecting]] — pipeline acquisizione B2B: stage, slice shipped, mappa issue/milestone
- [[Modello-dati]] — tabelle e enum del dominio prospecting + agenti AG-UI
- [[Convenzioni]] — regole di sviluppo del modulo (XotBase, Pest, gate qualità)
- [[GitHub-ops]] — mappa issue/label/milestone/discussion/actions
- [[References]] — fonti di pattern (closer-skill, sellbird)
