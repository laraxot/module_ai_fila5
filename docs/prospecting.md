---
title: "B2B prospecting (agnostic)"
module: AI
tags: [bmad, ecc, prospecting, closer-skill, sellbird]
issue: 32
updated: 2026-11-27
---

# Prospecting engine (issue #32)

## Study map

| Closer-skill | Sellbird | Module AI |
|--------------|----------|-----------|
| prospector audit/rank/draft | PROFILE signals | `DetectWebsiteSignalsAction` + `WebsiteSignalCatalog` |
| researcher brief | why they'd buy | `BuildProspectBriefAction` |
| stop / suppression | outbox approval | `ApplyProspectSuppressionAction` + `requires_approval` |
| pipeline board | discover→verify | `NormalizeProspectImportAction` + rank |

AGPL Sellbird is **not** vendored. Patterns only.

## Website signals (deepened 2026-11-27)

Contract: [docs/contracts/website-signals.md](contracts/website-signals.md).

- **Offline-first:** `analyzeHtml` / `analyzeHtmlResult` — host supplies HTML after a safe fetch.
- **Fail-closed:** `execute($url)` does not open network sockets (SSRF).
- **Polarity:** `gap` (rank score) vs `strength` (score 0, brief only).
- **Catalog:** mobile, SEO (title/meta/canonical/OG/H1/lang/JSON-LD), performance, HTTPS hint, trust, booking, chat, forms, analytics, social, phone, copyright, commerce ICP, hospitality ICP (`no-menu` / `no-hours` / `no-order-online`).
- **Weights:** `config('ai.prospect.signals.weights')` overrides `WebsiteSignalCatalog::defaults()`.

## ECC non-negotiables

1. Public HTML only; no private/login scrape in this action
2. No invented personal emails
3. No automatic send — drafts only
4. Signals = hints unless `confidence=measured|error`
5. Suppression honored on re-import / draft

## CLI

```bash
php artisan ai:prospects:discover restaurant-sales "Treviso" --research=20  # SPERIMENTALE: non eseguire prima del gate #33 + policy provider
php artisan ai:prospects:import demo tests/Fixtures/prospects-demo.csv      # dry-run when present
php artisan ai:prospect-audit https://example.com --type=business           # vecchio percorso Prospect/: resta chiuso (#40)
```

## Lettura dei siti sperimentale: SSRF non ancora risolta (#33)

- **Gate separato, spento per default:** oltre a `AI_PROSPECTING_ENABLED=true`, l'host deve impostare esplicitamente `AI_PROSPECTING_SITE_FETCH_ENABLED=true` per attivare la lettura live (`ai.prospecting.site_fetch_enabled`). Anche le chiamate **dirette** a `FetchPublicPageAction` sono bloccate quando questo secondo gate è spento. **Non attivare questo gate in produzione** finché la issue #33 non è risolta e verificata. Import e analisi offline non richiedono tale autorizzazione.
- `Prospecting/FetchPublicPageAction` + `ResolveHostAction`: solo http/https; ogni host, e ogni redirect (max 3,
  seguiti a mano), è preceduto da una verifica **solo IPv4 A** che rifiuta IP privati noti. **Questa NON è una garanzia SSRF-safe**: `gethostbynamel()` non verifica `AAAA` IPv6, `Http::get()` può scegliere IP diversi da quelli controllati (TOCTOU/DNS rebinding), e `substr($response->body())` limita il testo in memoria solo **dopo** il download completo. User-info, porte, URL/redirect canonici e un limite di download in streaming vanno testati. Non esporre il fetcher a input esterni né attivarlo in produzione prima della soluzione #33.
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

## Status (#32)

- [x] Deep offline signal catalog + Pest
- [x] Configurable weights
- [ ] Adapter `Prospecting/FetchPublicPageAction` **sperimentale e non ancora SSRF-safe** (#33): servono IP pinning/egress policy, IPv6/AAAA e limiti di streaming; `Prospect/DetectWebsiteSignalsAction::execute()` resta chiuso finché #40 non unifica i due namespace.
- [ ] Filament review UI, durable consent, provenance (#36–#41)
