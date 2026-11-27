---
title: "B2B prospecting (agnostic)"
module: AI
tags: [bmad, ecc, prospecting, closer-skill, sellbird]
issue: 32
updated: 2026-11-27
---

# Prospecting engine (issue #32)

## Study map (Closer MIT + Sellbird AGPL patterns only)

| Closer-skill | Sellbird | Module AI |
|--------------|----------|-----------|
| prospector audit/rank/draft | PROFILE signals | `DetectWebsiteSignalsAction` + `WebsiteSignalCatalog` |
| researcher brief | why they'd buy | `BuildProspectBriefAction` / `ResearchProspectAction` |
| stop / suppression | outbox approval | `ApplyProspectSuppressionAction` + `requires_approval` |
| pipeline board | discover→verify | `NormalizeProspectImportAction` + rank + `AiProspect.stage` |
| record-keeper | events | audit / host CRM via `AiActionProposal` only |
| follow-up / win-back / coach / forecast | sequences | optional later (#35), never auto-send |

AGPL Sellbird is **not** vendored. Patterns only. Closer is conceptual reference (MIT).

## Website signals (deepened 2026-11-27)

Contract: [docs/contracts/website-signals.md](contracts/website-signals.md).

- **Offline-first:** `analyzeHtml` / `analyzeHtmlResult` — host supplies HTML after a safe fetch.
- **Fail-closed:** `execute($url)` does not open network sockets (SSRF).
- **Polarity:** `gap` (rank score) vs `strength` (score 0, brief only).
- **Catalog:** mobile, SEO (title/meta/canonical/OG/H1/lang/JSON-LD), performance, HTTPS hint, trust, booking, chat, forms, analytics, social, phone, copyright, commerce ICP, hospitality ICP (`no-menu` / `no-hours` / `no-order-online`).
- **Weights:** `config('ai.prospect.signals.weights')` overrides `WebsiteSignalCatalog::defaults()`.
- **`no-site` weight = 0** (2026-11-27): failed/unknown reachability must not rank as opportunity; `WebsiteAuditResultData::totalScore()` already returns 0 when `ok=false`.

## ECC non-negotiables

1. Public HTML only; no private/login scrape in this action
2. No invented personal emails
3. No automatic send — drafts only
4. Signals = hints unless `confidence=measured|error`
5. Suppression honored on re-import / draft
6. Confirm path: only `PENDING` → confirm → execute (`ConfirmAiActionProposalAction`, #42)

## CLI

```bash
php artisan ai:prospects:discover restaurant-sales "Treviso"               # dry-run; richiede opt-in provider autorizzato
php artisan ai:prospects:import demo tests/Fixtures/prospects-demo.csv      # dry-run when present
php artisan ai:prospect-audit https://example.com --type=business           # vecchio percorso Prospect/: resta chiuso (#40)
```

## Lettura dei siti sospesa: SSRF non ancora risolta (#33)

- **Gate P0:** `FetchPublicPageAction::execute()` ora restituisce sempre un errore esplicito e non apre socket, persino con flag sperimentali attivi. La lettura di siti dal vivo resta sospesa finché non è disponibile un trasporto indipendentemente verificato (IP pinning, IPv6/AAAA, redirect, limiti byte pre-buffering, proxy). Importazione e analisi HTML offline restano attivi.
- **Perché:** la precedente implementazione controllava solo indirizzi IPv4 A prima della connessione, senza garantirne l'IP effettivo (DNS rebinding/TOCTOU); inoltre tagliava i byte soltanto **dopo** il download. `ResolveHostAction` è conservata come legacy, non come garanzia SSRF.
- `ResearchProspectWebsiteAction`: legge il sito del prospect con la lettura sicura e lo passa a
  `ResearchProspectAction`. Se la lettura fallisce **non** assegna punteggio e non tocca il prospect.
- `OsmRequestAction` (unica porta verso OSM: interruttore `ai.prospecting.enabled`, User-Agent, max 1 richiesta/s
  per servizio con lock, cache), `GeocodeAreaAction` (Nominatim, cache 30 giorni), `DiscoverProspectsInAreaAction`
  (Overpass → `ImportOsmCandidatesAction` → `ImportProspectCandidatesAction`). Categorie in
  `ai.prospecting.osm_categories` (default del pilota ristoranti). Attribuzione "© OpenStreetMap contributors".
- Prove: `tests/Unit/Prospecting/LiveDiscoveryAndSafeFetchTest.php` (gruppo `AI-PROSPECTING-INV-33`); dal vivo
  Nominatim risponde in 0,8 s; Overpass dalla sandbox di sviluppo non è raggiungibile (proxy).

## Regole italiane sull'email

Il Garante privacy: un indirizzo email pubblicato online **non autorizza** comunicazioni promozionali senza
consenso, e il link di disiscrizione non lo sostituisce ([provv. 21/09/2017](https://garanteprivacy.it/garante/doc.jsp?ID=7221917)).
Molti ristoranti sono ditte individuali (persone fisiche). Il motore non invia nulla e non raccoglie email
personali; il primo contatto naturale è la visita o la telefonata. Non è una consulenza legale (#36).

## Status (#32) — swarm 2026-11-27

- [x] Deep offline signal catalog + Pest
- [x] Configurable weights
- [x] `no-site` weight 0 + totalScore fail-closed
- [x] Confirm replay guard: non-PENDING rejected (Refs #42) — host Pest still required
- [ ] Adapter `Prospecting/FetchPublicPageAction` **disabilitato fino a revisione #33**: IP pinning/egress, IPv6/AAAA, streaming limits; unify namespaces (#40)
- [ ] Filament review UI, durable consent, provenance (#36–#41)
- [ ] Projects / Milestones / shared Issue Views on GitHub UI (specified in `docs/github-ops/00-index.md`, not created in API session)

## Continuazione operativa — dry-run by default

Il comando `ai:prospects:audit-html` è registrato per analizzare in modo deterministico HTML già ottenuto lecitamente, con fonte e punteggio spiegabile, senza DB, rete o invii:

```bash
php artisan ai:prospects:audit-html https://www.openstreetmap.org/node/11 /approved/path/page.html --website=https://business.example
```

La discovery via Nominatim/Overpass richiede **due** opt-in distinti: `AI_PROSPECTING_ENABLED=true` e `AI_PROSPECTING_PUBLIC_DISCOVERY_ENABLED=true` (default false). Il progetto host verifica licenza, ToS, contatto User-Agent e costi. L'esecuzione è **dry-run** a meno di `--apply`; in produzione occorrono anche `--allow-production` e `AI_PROSPECTING_PRODUCTION_IMPORTS_ENABLED=true`, con autorizzazione dell'operatore. Bounding box grandi vengono bloccati. È vietato impiegare endpoint OSM pubblici per estrazioni commerciali ricorrenti senza consenso/fornitore compatibile.

I test Pest del progetto host, il livello PHPStan e le migrazioni isolate restano da eseguire e verificare: la CI di sintassi non li sostituisce.
