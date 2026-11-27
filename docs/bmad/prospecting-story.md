---
id: ai-prospect-32
title: "BMAD epic — acquisizione aziende B2B riusabile"
type: epic
status: implementation-in-progress
module: AI
created: 2026-11-27
updated: 2026-11-27
qmd: "AI prospecting Closer Sellbird B2B BMAD requirements architecture user stories ECC acceptance"
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
related:
  - ../prompts/clients.md
  - ../prospecting.md
  - ../github-ops/00-index.md
---

# Epic #32 — Prospect Research → Review → CRM Handoff

## Problem / outcome

Operatori di progetti diversi hanno bisogno di trovare aziende compatibili con la loro offerta, motivare la scelta con fonti, rivedere la ricerca, preparare un contatto *consentito* e misurare l'esito. L'AI deve risolvere ricerca e classificazione senza assumere il controllo del dominio CRM e senza effettuare marketing automatico.

**North-star verificabile:** numero di aziende con fonte lecita, import ripetibile, motivazione e revisione umana; non numero di email generate o spedite. Numeri e conversion rate richiedono eventi osservati.

## BMAD product requirements

- **R1 / Agnostico**: `Modules/AI` non importa model di Restaurant/Village/Ticket, non memorizza offerte, listini o vendite dell'host. L'host configura ICP, autorizza workspace, possiede CRM, calendario e invii.
- **R2 / Discovery lecita**: import CSV/OSM-export da fonti consentite, preview, attribuzione e licensing; niente scraping/login, credenziali o email inferite.
- **R3 / Evidence first**: ogni segnale distingue osservazione, ipotesi e assenza non comprovata; timestamp, provenienza, confidenza e TTL.
- **R4 / Identità**: ID per workspace robusti a reimport e cambi di nome/dominio, collisioni/rebranding/multi-sede esplicitati.
- **R5 / Qualificazione**: ICP versionato, pesi, motivi di esclusione, score riproducibile, no punti da errori HTTP.
- **R6 / Review**: vista Filament role-scoped; proposta di brief/draft con revisione umana e nessun invio.
- **R7 / Privacy**: basi giuridiche/consensi per finalità e canale, DNC persistente, minimizzazione, retention, cancellazione e audit.
- **R8 / Interoperabilità**: un adapter host per form inbound, lead, CRM, appointment, reply; side effects tramite `AiActionProposal` / handler di dominio.
- **R9 / ECC**: test host, PHPStan, Pest, Pint e threat tests; CI con attestazioni. Nessuna dichiarazione “100%” senza misurazione.
- **R10 / Second Brain**: docs con frontmatter QMD, decision log, fonti, commit e confidence aggiornati senza duplicare canon Xot.

## Architettura (testo sequenziale)

1. **Host** definisce `workspace`, ICP e authorized sources; autorizza l'operatore.
2. **AI intake** valida CSV/export, normalizza, conserva source/provider/license e non raccoglie PII non necessarie.
3. **AI storage** gestisce aziende, snapshot delle osservazioni e tombstone di soppressione.
4. **Host-safe fetch** (non implementato) può fornire solo HTML pubblico acquisito da adapter con protezione SSRF; l'AI non effettua richieste arbitrarie.
5. **AI research** rileva segnali contestualizzati, li mostra come indizi, costruisce ranking spiegabile e brief.
6. **Filament** presenta evidenze, richiede review e propone una bozza. `send_permitted=false` è default.
7. **Domain handler** dell'host conferma eventuali attività CRM o eventi di calendario con consenso/documenti e permessi; AI non esegue invii.
8. **Telemetry** conserva eventi minimizzati; l'host calcola funnel reale, costi e qualità.

## Stato implementativo verificato staticamente (non runtime)

- Commit `07a137d`: vecchio percorso DTO puro `Actions/Prospect/*`, CLI `ai:prospect-audit`, test mirati.
- Commit `cc689ea`: `Actions/Prospecting/*`, `AiProspect`, `AiProspectSuppression`, migrazioni, CSV/OSM import dry-run, `ai:prospects:import`, action per bozza interna, syntax workflow.
- Il fetch diretto insicuro nella precedente Action è stato disattivato (fail-closed); una nuova soluzione va specificata e testata.
- **Attenzione:** due namespace `Prospect` e `Prospecting` convivono; occorre una decisione di consolidamento prima di esporre API pubbliche.
- Non sono stati eseguiti qui `php -l`, Pest, Pint, PHPStan o migrazioni su host; il commit non è una certificazione di release.

## Story e dipendenze

| Ordine | Story | Issue | Definition of Done |
|---|---|---|---|
| 0 | Sicurezza fetch e failure score | [#33](https://github.com/laraxot/module_ai_fila5/issues/33) | SSRF red-team, fail-closed e ranking zero su failure |
| 0 | Privacy e consenso | [#36](https://github.com/laraxot/module_ai_fila5/issues/36) | Policy per canale, prova consenso e DNC duraturo |
| 1 | Data model + tenant | [#40](https://github.com/laraxot/module_ai_fila5/issues/40) | Isolation, migrazioni e dedupe verificati in host |
| 1 | Import/providers | [#39](https://github.com/laraxot/module_ai_fila5/issues/39) | CSV/OSM idempotente, licenze e dry-run |
| 2 | Signals / ICP / score | [#37](https://github.com/laraxot/module_ai_fila5/issues/37) | Audit versionato, confronto contro fixture avverse |
| 2 | Filament review | [#38](https://github.com/laraxot/module_ai_fila5/issues/38) | RBAC, audit trail e screenshot reali QA |
| 2 | Inbound + CRM adapters | [#34](https://github.com/laraxot/module_ai_fila5/issues/34) | Webhook sicuro, owner in host, approvazione |
| 3 | ECC CI | [#41](https://github.com/laraxot/module_ai_fila5/issues/41) | Gate di host verdi e report riproducibili |
| 4 | 11 skill Closer | [#35](https://github.com/laraxot/module_ai_fila5/issues/35) | Feature singole motivate da pilot e consenso |

## Critical quality risks

- **Security**: un URL derivato da dati esterni non deve diventare richiesta HTTP dal runtime applicativo. Fail closed finché non c'è un adapter sicuro.
- **Compliance**: l'eventuale bozza non autorizza invio; in Italia consenso preventivo per marketing elettronico, salvo deroghe specifiche, da validare con DPO.
- **Data integrity**: il fingerprint sul solo dominio potrebbe aggregare sedi/aziende diverse; serve identità provider/external ID e merge assistito.
- **Domain leakage**: `ImportOsmCandidatesAction` inizialmente filtra soltanto categorie ristorazione; trasformare in filtro del chiamante per riuso trasversale.
- **Duplicate paths**: convergere `Prospect` vs `Prospecting` con contratto unico e migrazione controllata.
- **Runtime**: `Actions` e migrazioni dipendono dal progetto host Xot/Tenant/UI, CI sintassi non basta.

## Exit criteria and handoff

Qualunque story passa a **Verified** solo con: issue collegata, file/commit esatti, prova su host (PHP 8.4 + Laravel 13 + Filament 5), test e PHPStan, review della sicurezza, docs QMD aggiornati. Le funzioni che trasferiscono dati personali, prenotano o inviano richiedono autorizzazioni e policy separate.

Per la coda di lavoro concreta: [operations map](../github-ops/00-index.md) e [#32](https://github.com/laraxot/module_ai_fila5/issues/32).
