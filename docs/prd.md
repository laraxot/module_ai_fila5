---
title: "AI Module — Product Requirements Document"
type: product-requirements
module: AI
status: living
created: 2026-09-26
updated: 2026-11-27
qmd: "Laraxot AI module PRD agent assistant predictions prospecting reusable Actions privacy tenant LLM"
tags: [prd, bmad, architecture, ai, prospecting]
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/26"
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
  - "https://github.com/laraxot/module_ai_fila5/issues/41"
related:
  - ./adr/0001-ai-proposes-human-confirms.md
  - ./bmad/prospecting-story.md
  - ./github-ops/00-index.md
---

# AI Module — Product Requirements Document (BMAD)

> Documento vivente del modulo trasversale **AI**. Non assegnare percentuali di completamento senza test o una matrice di requisiti verificati. Lo stato dipende anche dall'applicazione ospitante.

## Visione e confini

`Modules/AI` espone capacità AI riutilizzabili e controllate: integrazione provider, thread, base di conoscenza, interpretazione/suggerimento, agenti e tool, proposte di azione con conferma, estrazione da immagini, e **ricerca/qualificazione aziende B2B**. È un'infrastruttura **agnostica rispetto al dominio**. I moduli host (Restaurant, TechPlanner, Village, Ticket, ecc.) decidono business rules, offerte, customer records, accessi e azioni operative. AI non esegue decisioni finali irreversibili.

Nessun contenuto di questo PRD autorizza invii di marketing, scraping non consentito, attivazione di servizi a pagamento o modifiche di produzione.

## Utenti e bisogni

| Persona | Lavoro da svolgere | Limite da rispettare |
|---|---|---|
| Integratore modulo host | Chiamare Action tipizzate, modificare prompt/config e registrare handler | Non creare dipendenze AI → dominio host |
| Operatore back office | Consultare conoscenza, proposte e prospect; approvare bozze | Accesso per workspace/ruolo e audit |
| Product owner | Valutare qualità, costi, errori, funnel e valore | Indicatori reali, non dedotti |
| Security/privacy owner | Impostare permessi, policy e limiti di trattamento | Consenso, minimizzazione, DNC e retention |
| Utente finale | Ricevere assistenza e eventuale passaggio a persona | Nessuna azione AI incontrollata |

## Scope funzionale

### Core AI già rappresentato nel repository

- Action per richieste a provider AI con configurazione, errori e fallback.
- Assistente basato su `AiKnowledgeEntry` e handoff quando non c'è risposta affidabile.
- `AiActionProposal` con registro `AiActionHandlerRegistry`: proposta dal modello, conferma da persona, handler nel dominio host (vedi ADR-0001).
- AG-UI / Neuron e MCP per agenti assistiti, con tool che richiedono approval.
- Vision/extraction, sentiment, prediction drafts come **casi di utilizzo**; le regole finali restano del host.
- Funzioni di prospecting B2B: DTO/Action pure, import CSV/OSM-export, persistenza aziendale, scoring indiziario, brief, bozza interna e soppressione; **stato provvisorio** finché gate #33/#36/#41 non sono verdi.

### Non in scope per il modulo AI

- Proprietà dei modelli CRM, quote, fatture, pipeline commerciale del progetto host.
- Invio automatico di cold email/SMS/call, selezione autonoma di destinatari, registrazioni vocali senza consenso.
- Uso del servizio Overpass pubblico come provider commerciale non autorizzato; scraping di dati personali/aree protette.
- Copia di codice AGPL Sellbird in un progetto MIT senza review legale/di licenza.

## Requisiti funzionali verificabili

| ID | Priorità | Requisito e accettazione |
|---|---|---|
| AI-001 | P0 | Tutte le Actions hanno contratti espliciti e testabili senza accesso reale ai provider; output parsati/validati |
| AI-002 | P0 | Nessun side effect di dominio senza approvazione e verifica server-side dello stato (no replay/race) |
| AI-003 | P0 | Autorizzazione su workspace, modello, thread e strumenti per ogni chiamata esposta |
| AI-004 | P1 | Provider e fallback sostituibili; logging redatto, costi e limiti osservabili |
| AI-005 | P1 | Base di conoscenza isolata e provenance delle risposte; domanda ignota → handoff |
| PRO-001 | P0 | Sorgenti lecite, consenso e suppression duratura prima di interazioni personali (#36) |
| PRO-002 | P0 | Nessun fetch remoto arbitario/SSRF e failure senza score positivo (#33) |
| PRO-003 | P1 | Import CSV/OSM-export e provider adapter con licenza/costo, preview e idempotenza (#39) |
| PRO-004 | P1 | `AiProspect` e osservazioni isolate per workspace, identità robuste a sedi/rebrand (#40) |
| PRO-005 | P1 | Evidence timestamp/confidence + ICP e score riproducibili; claim verificabili (#37) |
| PRO-006 | P1 | Back office Filament con vista per operatori, review, audit e niente invio (#38) |
| PRO-007 | P1 | Inbound consensuale e CRM/calendario tramite adapter/handler host (#34) |
| PRO-008 | P2 | Suggerimenti di follow-up, proposta, coach e forecast come capacità opzionali (#35) |

## Qualità non funzionale / ECC

- PHP 8.4, Laravel 13, Filament 5 secondo host; classi Action conformi a Spatie QueueableAction con `execute()`.
- Coerenza Laraxot: `XotBaseModel`, `XotBaseMigration`, policy Xot/Filament e contratti espliciti; no nuovo modulo.
- Target PHPStan di progetto, Pint, Pest unit/integration e test browser dove necessario; esecuzione sul host obbligatoria prima di “verified”.
- Resilienza: dry-run default, rate/cost budgets, error states distinti, retry idempotenti, concurrency-safe, audit senza dati personali in chiaro.
- Security: SSRF, prompt injection, CSV/import injection, excessive size, secret leak, RBAC, multi-tenant, rights-to-erasure, consent and DNC.
- Privacy by design: data minimization e scadenza delle osservazioni; provider license e finalità sono parte dell'import.
- Documentation: BMAD PRD → ADR → story → ECC evidence → Second Brain QMD. Nessun file spezzato o link a path inesistenti accettato come definitivo.

## Success metrics — da misurare, non inventare

- Qualità: percentuale di osservazioni con fonte verificabile, tasso di duplicati, falsi positivi dei segnali, soppressioni correttamente propagate.
- Affidabilità: test veri/verdi, regressioni, vulnerabilità aperte, tentativi di accesso inter-workspace bloccati, error budget provider.
- Funnel: `discovered`, `qualified`, `reviewed`, `consented`, `handed_off`, `meeting`, `won`, distinti e derivati da eventi reali dell'host.
- Efficienza: tempo umano per ricerca e verifica, costo per fonte/lavorazione quando disponibile.

## Delivery plan

- **Gate 0 — Safety**: [#33](https://github.com/laraxot/module_ai_fila5/issues/33), [#36](https://github.com/laraxot/module_ai_fila5/issues/36), [#42](https://github.com/laraxot/module_ai_fila5/issues/42) per approval replay.
- **Gate 1 — Data**: [#40](https://github.com/laraxot/module_ai_fila5/issues/40), [#39](https://github.com/laraxot/module_ai_fila5/issues/39).
- **Gate 2 — Intelligence & UX**: [#37](https://github.com/laraxot/module_ai_fila5/issues/37), [#38](https://github.com/laraxot/module_ai_fila5/issues/38), [#34](https://github.com/laraxot/module_ai_fila5/issues/34).
- **Gate 3 — Runtime readiness**: [#41](https://github.com/laraxot/module_ai_fila5/issues/41), con evidence da app host; poi [#35](https://github.com/laraxot/module_ai_fila5/issues/35).

Le implementazioni parziali esistenti su `dev` non spostano un gate a “done” senza test e review. Per l'architettura e gli acceptance criteri tecnici usa [prospecting BMAD story](./bmad/prospecting-story.md), per tutte le superfici GitHub usa [operations map](./github-ops/00-index.md).

## Rischi e decisioni aperte

1. `Prospect` vs `Prospecting`: consolidare namespace senza rompere chi usa le Action.
2. Filtro OSM ristorazione hardcoded nel modulo condiviso: renderlo parametro provider/host.
3. Dedupe su solo dominio: può collassare più sedi aziendali.
4. Consent registry per canali/territori, retention e gestione rettifiche non ancora completo.
5. Host-migration e PHPStan/Pest non verificati in questa analisi.

**Conferma del risultato**: code review del branch `dev`, acceptance per ogni requisito, policy privacy validata, verifica runtime e fonti delle metriche.
