---
title: "Ricerca clienti B2B — studio Closer e Sellbird"
type: research-and-bmad-story
module: AI
status: implementation-slice
updated: 2026-11-27
qmd: "prospecting b2b lead research Closer Sellbird outreach consent GDPR OSM ICP"
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
  - "https://github.com/festionali/base_restaurant_fila5/issues/86"
---

# Ricerca clienti B2B nel modulo AI

## Fonti studiate

- [Closer skill](https://github.com/Jakeschincariol/closer-skill) — **MIT**: undici skill; principalmente `prospector`, `researcher`, `pipeline`, `follow-up`, `record-keeper`. Valore: schede semplici, origine verificabile dei lead, approvazione **uno per uno**, soppressione permanente. Il flusso originario usa CLI Python e dati JSON locali; non è un CRM multi-tenant Laravel.
- [Sellbird](https://github.com/ethanplusai/sellbird) — **AGPL-3.0**: discovery/profilazione/enrichment/scoring/approval/outbox/CRM, segnali di acquisto e cohort, guard pre-send, traccia costi e consensi. Abbiamo tratto **requisiti e idee**, non copiato codice, così il modulo MIT resta indipendente da vincoli AGPL del codice sorgente.
- [Signal pipeline](https://github.com/ethanplusai/sellbird/blob/main/docs/signals-and-pipeline.md), [Approval & sending](https://github.com/ethanplusai/sellbird/blob/main/docs/approval-and-sending.md).

## Decisione architetturale (BMAD + ECC)

`Modules/AI` è **agnostico**: non conosce `Restaurant`, `Village`, `Ticket` o i prezzi del gestionale. Il progetto host fornisce `workspace`, ICP, offerta e sorgenti lecite. Il modulo gestisce **aziende**, non persone: `AiProspect`, `AiProspectSuppression` e Actions pure/per-scope. Anche qualora in futuro ci fosse un contatto, **mai** eseguire un invio senza approvazione specifica, verifiche normative e autorizzazione del progetto.

| Dallo studio | Scelta nel modulo AI | Attuale stato |
|---|---|---|
| Closer: fonte e audit pubblico | `NormalizeProspectCandidateAction`, `ImportProspectCandidatesAction` | Codice introdotto; test integrazione DB da eseguire |
| Sellbird: fonte OSM | `ImportOsmCandidatesAction` da **export esistente** (nessun crawl automatico) | Codice introdotto |
| Sellbird: segnali con prova | `ExtractProspectSignalsAction`, `ResearchProspectAction` | Codice introdotto, HTML ricevuto dal chiamante |
| Sellbird: punteggio spiegabile | `ScoreProspectAction`, pesi configurabili | Codice introdotto; non equivale a purchase intent provato |
| Closer: pre-call research | `BuildProspectBriefAction` | Codice introdotto |
| Closer: bozze, approvazione | `DraftProspectMessageAction` | **Solo bozza**, senza invio o destinatario |
| Closer/Sellbird: stop al no | `SuppressProspectAction`, tombstone persistente | Codice introdotto |
| CRM filtrato per workspace | `ListProspectsAction` | Codice introdotto, nessuna route pubblica |
| Pilot import | `ai:prospects:import` CSV/OSM con dry-run default | Codice introdotto, applicazione solo con flag |

## Invarianti di sicurezza

1. `workspace` sempre obbligatorio nelle letture/scritture; l'**host deve autorizzare** il workspace prima di chiamare una Action. Non esporre queste Actions direttamente a form pubblici. Test A/B multi-workspace prima di distribuire la UI.
2. Source URL di ogni azienda e segnale; niente email/telefono o informazioni private importate da OSM. `website` viene conservato come metadata, **mai** visitato direttamente dalle Actions.
3. Con import ripetuti la stessa azienda non genera duplicati. Una suppressione non è rimossa dal re-import, dal reset del punteggio o da un nuovo draft.
4. L'HTML è un input **non fidato**: nessun prompt o comando implicito, nessuna esecuzione o rete. L'assenza di un widget nella pagina non dimostra l'assenza della funzione. `observed_hint` richiede verifica manuale.
   Il vecchio `DetectWebsiteSignalsAction::execute()` è disabilitato per il fetch remoto finché non esiste un adapter con protezione SSRF completa; `analyzeHtml()` resta utilizzabile offline su HTML autorizzato.
5. `DraftProspectMessageAction` restituisce `send_permitted=false` e non ha provider email, SMTP, Mailjet o Gmail. In Italia anche indirizzi B2B pubblici non comportano consenso alle email promozionali: [Garante, provvedimento 23/10/2025](https://www.garanteprivacy.it/web/guest/home/docweb/-/docweb-display/docweb/10197559), [ulteriore provvedimento](https://www.garanteprivacy.it/home/docweb/-/docweb-display/print/10224476).
6. Gli endpoint Overpass **pubblici** non vanno usati per discovery commerciale ricorrente: serve provider compatibile/hosted o export legittimo; rispettare [Overpass usage policy](https://wiki.openstreetmap.org/wiki/Overpass_API), licenza ODbL e attribuzione OSM.
7. Nessun LLM viene chiamato per dedupe, scoring o regole di consenso. Il modello può proporre miglioramenti testuali solo con revisione umana; segue [ADR human-in-the-loop](../adr/0001-ai-proposes-human-confirms.md).

## Import di prova (solo dati fittizi)

Fixture: `tests/Fixtures/prospects-demo.csv` e `tests/Fixtures/osm-demo.json` (aziende `.example`, nessun destinatario).

```bash
cd laravel
php artisan ai:prospects:import restaurant-demo Modules/AI/tests/Fixtures/prospects-demo.csv
php artisan ai:prospects:import restaurant-demo Modules/AI/tests/Fixtures/osm-demo.json --osm
```

I comandi sopra **validano senza salvare**. Solo in un ambiente isolato **dopo** consenso del responsabile, impostare `AI_PROSPECTING_ENABLED=true` e aggiungere `--apply`; produzione richiede anche `--allow-production` esplicito. Nessun seeder demo deve toccare un DB vero. Prima di importare: `php artisan migrate --env=testing` esclusivamente su DB `*_test` validato dall'host.

CSV minimo: `name,website,category,city,country,source_url,source_provider`. `source_url` è obbligatorio; una riga senza sito può essere valida se la fonte pubblica è verificabile. Il codice limite import è 250 per batch / 10.000 righe per file.

## Piano di completamento / Acceptance Criteria

- [x] Studio delle due fonti e scelta di un'architettura senza dipendenze dal codice AGPL.
- [x] Modelli, migrazioni additive, pipeline pure, import CLI in sola validazione predefinita.
- [x] Pest unit per normalizzazione, dati non pubblici, segnali, punteggio e OSM.
- [ ] Eseguire PHPStan livello 10, Pest e migrazioni sul progetto Laravel ospite con DB effimero; non chiamare questi test verdi senza run.
- [ ] Adapter HTTP server-side sicuro contro SSRF, redirect, DNS rebinding, rate/budget, robots e ToS; no spider diretto dentro Actions AI.
- [ ] Filament UI protetta e isolata per workspace con policies e access logs (no vista globale).
- [ ] Provider commerciale di discovery approvato dall'owner: credenziali e tariffe **non** incluse, preventivo costi prima delle richieste.
- [ ] Pipeline su risposte, calendari, proposte e conversioni; implementarla solo dopo processo commerciale e consenso verificati.
- [ ] ICP Restaurant e primo pilota con 2 workspace/2 location, privacy review, dedupe e signal precision misurati.

## Metriche sensate

`importati`, `duplicati evitati`, `soppressi`, `% con fonte verificabile`, `% segnali confermati manualmente`, `qualificati → colloqui` (solo a posteriori). **Non** promettere conversioni, ROI o automazioni email non provate. Story: [AI #32](https://github.com/laraxot/module_ai_fila5/issues/32), [Restaurant #86](https://github.com/festionali/base_restaurant_fila5/issues/86).
