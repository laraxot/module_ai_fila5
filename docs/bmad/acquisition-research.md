---
title: "Acquisition research — B2B client acquisition per il modulo AI"
type: research
module: AI
tags: [bmad, ecc, prospecting, acquisition, closer-skill, sellbird]
created: 2026-11-27
updated: 2026-11-27
status: draft
qmd: "AI acquisition research B2B prospecting pipeline signals guardrails"
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
  - "https://github.com/laraxot/module_ai_fila5/issues/33"
  - "https://github.com/laraxot/module_ai_fila5/issues/34"
  - "https://github.com/laraxot/module_ai_fila5/issues/35"
  - "https://github.com/laraxot/module_ai_fila5/issues/36"
  - "https://github.com/laraxot/module_ai_fila5/issues/37"
  - "https://github.com/laraxot/module_ai_fila5/issues/38"
  - "https://github.com/laraxot/module_ai_fila5/issues/39"
  - "https://github.com/laraxot/module_ai_fila5/issues/40"
  - "https://github.com/laraxot/module_ai_fila5/issues/41"
  - "https://github.com/laraxot/module_ai_fila5/issues/42"
  - "https://github.com/laraxot/module_ai_fila5/issues/43"
discussions: []
related:
  - ../prospecting.md
  - prospecting-story.md
  - acquisition-epics.md
  - acquisition-stories.md
  - ../wiki/bmad-method.md
---

# Research report — acquisizione clienti B2B nel modulo AI

> Scopo: fondare il piano epic/story (`acquisition-epics.md`, `acquisition-stories.md`)
> sul lavoro già pianificato (issue #32–#43 su `laraxot/module_ai_fila5`) e sui
> riferimenti clonati in `/tmp/closer-skill` (MIT) e `/tmp/sellbird` (AGPL — pattern only,
> mai vendored). Studio approfondito parallelo:
> `docs/sources/2026-11-27-closer-sellbird-study.md`.

## 1. Landscape

### 1.1 Closer skill (MIT, 11 skill Claude)

Repository: `github.com/Jakeschincariol/closer-skill`. Undici skill che coprono
l'intero ciclo vendite dentro Claude, senza CRM a pagamento:

| Skill | Job | Cosa insegna al modulo |
|---|---|---|
| `record-keeper` | Log di email e call | Ogni interazione persistita e recuperabile per contatto |
| `prospector` | Trovare business locali, audit gap, cold email | Stadio discover→audit→draft end-to-end |
| `follow-up` | Follow-up fino a call booked, stop al primo no | Sequenza di follow-up con stop condition |
| `researcher` | Brief "perché comprerebbe" | Sintesi evidenze → motivazione |
| `pipeline` | Bacheca deal (`pipeline/deals.py` condiviso) | Modello stage CRM minimale condiviso |
| `scheduler` | Prenotazione call | Handoff verso calendario |
| `forecaster` | Forecast da pipeline | Stima da stadi + probabilità |
| `win-back` | Recupero prospect dormienti | Segmentazione per ultimo contatto |
| `proposal-writer` / `contract-writer` | Proposte e contratti | Fuori scope acquisizione iniziale (post-booking) |
| `sales-coach` | Coaching su chiamata | Fuori scope (live call) |

Principio cardine Closer: **"nothing is ever sent, booked or signed without your
yes — Claude drafts, you approve"**. È esattamente il modello `requires_approval`
già presente nel modulo.

### 1.2 Sellbird (AGPL-3.0, "Harvey")

Repository: `github.com/ethanplusai/sellbird`. Piattaforma outreach open-source che
vive dentro l'agente (Claude Code / Codex). Pipeline a 9 stadi documentata in
`docs/signals-and-pipeline.md` + `docs/approval-and-sending.md` +
`docs/deliverability.md` + `docs/inbound-leads.md` + `docs/social.md`.

Punti di forza da importare come *pattern* (mai codice, licenza AGPL):

- **Discover** con provider a pagamento espliciti (`sellbird discover --estimate`
  mostra il costo prima di eseguire); preset come `accelerator`, `hiring`.
- **Profile**: 3 request HTTP per business, nessuna chiamata modello, ispezione di
  homepage/robots/sitemap/team/careers → incumbent agency, ad pixel, schema
  mancante, ruoli aperti, nomi. Rilevamento agency con confidence scoring
  (0.9 "Powered by" / 0.75 link descrittivo / 0.35 non registrato).
- **Enrich**: persona decision-maker dal sito stesso (team/about/story/meet-the-X,
  max 6 pagine); ranking owner-first; **max 2 persone per azienda**; esclusione di
  testimonial, ex-owner, tag line; schema.org founder/employee; regole di titolo
  per parola intera (Vice President ≠ President).
- **Verify**: apprendimento *pattern* email per dominio da indirizzi pubblicati
  (`nvale@` → `{first initial}{last}`); decodifica obfuscation (Cloudflare email
  protection, `data-user`/`data-domain`, `[at]`/`[dot]`); mai inventare indirizzi;
  tagging onesto `verified|risky|guess|invalid` — solo deliverable vengono contattati.
- **Write/send**: sequenza corta per persona; invio da Gmail/M365/SMTP con daily
  limit e **office hours del destinatario** (timezone risolta da nazione/provincia);
  warmup ramp (`min_days`, `daily_increase`) e kill switch bounce >soglia.
- **Approval ladder**: Outbox come "decision desk" (una bozza alla volta, niente
  approve-all di riflesso); pre-send gate **deterministico, senza modello**
  (merge tag non renderizzati, frasi vietate, link eccessivi, HTML, destinatario
  non deliverable, mismatch DB); modalità autopilot con "Held" per link non propri,
  numeri di telefono estranei, richieste di pagamento.
- **Reply handling**: regole multilingua di opt-out (EN/HE/ES/FR), opt-out
  immediato e permanente ovunque nel testo, testo citato del mittente non è
  interpretato come parole del destinatario, bot disclosure veritiero se chiesto.
- **Deliverability legale**: CAN-SPAM (opt-out funzionante, indirizzo postale,
  subject veritiero), GDPR/PECR legitimate interest, check per-ricorrente opzionale
  (Precleared), LinkedIn off-by-default per ToS, warmup 2–4 settimane su dominio
  dedicato con SPF/DKIM/DMARC e TrulyInbox come provider warmup.
- **Inbound**: lead da form/sito/testi/menzioni con stesse regole di consenso.
- **Social**: playbook per canali social con approvazione esplicita; LinkedIn
  richiede doppio opt-in (`channels.linkedin.enabled` + credenziali).

### 1.3 Baseline commerciale: Apollo, Instantly, Clay

Riferimento di mercato per gap analysis (non integrazioni):

- **Apollo.io**: database B2B enorme (contatti+aziende), filtri ICP, email
  verification integrata, sequencer multi-step. Il nostro equivalente "database"
  non esiste: puntiamo su fonti pubbliche + CSV/API provider (#39) e qualità
  segnale sopra volume.
- **Instantly.ai**: deliverability-first — warmup pool, rotating mailbox,
  deliverability score, reply detection. Manca nel nostro piano → epic nuovo
  (deliverability/mailbox infra).
- **Clay**: orchestrazione di waterfall enrichment (più provider in fallback),
  waterfall di verifica email, "signals" come primo-class. Da cui importiamo
  l'idea di **waterfall di provider** in #39 e di verifica multi-adapter.

### 1.4 Gap vs. piano attuale (issue #32–#43)

Coperto: engine agnostico (#32), sicurezza fetch SSRF (#33), privacy/consenso
(#36), persistenza+tenant (#40), import fonti (#39), segnali/ICP/ranking (#37),
UX Filament (#38), qualità CI (#41), proposte azione sicure (#42), inbound+CRM
adapter+booking handoff (#34), 11 skill Closer (#35), ciclo outreach
(gate/follow-up/risposte/stadi/forecast) (#43).

Non coperto o sottocoperto → epic nuovi in `acquisition-epics.md`:
deliverability/warmup/mailbox infra, adapter di verifica email, touchpoint
LinkedIn/social, export/sync CRM bidirezionale, calendar/booking reale.

## 2. La pipeline di acquisizione a 9 stadi

Mappatura Sellbird + Closer sul vocabolario del modulo:

| # | Stadio | Input → Output | Riferimento | Issue |
|---|--------|----------------|-------------|-------|
| 1 | **Discover** | ICP query → lista business/aziende | Sellbird discover, Closer prospector | #32, #39 |
| 2 | **Profile** | dominio → fatti sito (agency, pixel, ruoli, schema) | Sellbird profile | #32, #37 |
| 3 | **Enrich** | azienda → persone decisionali + evidenze | Sellbird enrich, Closer prospector | #32, #40 |
| 4 | **Verify** | candidato contatto → stato deliverability + pattern dominio | Sellbird verify, Clay waterfall | #39, #40, **new** |
| 5 | **Qualify/Rank** | segnale+ICP → score spiegabile, brief, draft | Closer prospector, Sellbird cohort | #37, slice #32 |
| 6 | **Approve** | draft → invio solo dopo approvazione umana | Sellbird outbox, Closer "your yes" | #42, #43, #36 |
| 7 | **Send** | draft approvato → cassetta postale (limiti, quiet hours, warmup) | Sellbird send, Instantly | #43, **new** |
| 8 | **Reply** | risposta → classificazione, bozza risposta, opt-out, handoff | Sellbird reply, Closer follow-up/record-keeper | #43, #34, **new** |
| 9 | **Book/Forecast** | conversazione → appuntamento, stadio CRM, forecast | Closer scheduler/forecaster/pipeline | #34, #43, **new** |

Nota: "write" (stadio 5→draft) e "send" sono separati di proposito — il draft è
un artefatto revisionabile, l'invio è un'azione gated. La numerazione a 9 stadi
omette write perché in Sellbird è parte di PROFILE→VERIFY→WRITE; qui lo teniamo
implicito nello stadio Qualify/Rank e nel gate Approve.

## 3. Catalogo segnali proposto per il modulo

Ogni segnale = osservazione con `confidence` (`measured|estimated|hint`) ed
evidenza (URL + frammento). Regola ECC esistente: i segnali sono *hint* se non
`confidence=measured|error`.

### 3.1 Segnali sito (da fetch pubblico, già in `DetectWebsiteSignalsAction`)

| Segnale | Rilevamento | Confidence tipica | Perché conta |
|---|---|---|---|
| `site.no_online_booking` | assenza widget booking su homepage/team/contatti | estimated | gap diretto per seller SaaS booking |
| `site.not_mobile_friendly` | viewport/meta assenti | measured | costo percepito basso, fix facile |
| `site.slow_ttfb` | tempo risposta homepage | measured | proxy qualità web |
| `site.no_reviews_schema` | assenza aggregateRating/Review | measured | social proof mancante |
| `site.no_service_schema` | assenza LocalBusiness/Service schema | measured | SEO locale |
| `site.incumbent_agency` | footer "Powered by / Website by" + scoring | 0.9/0.75/0.35 | roster target per competitor displacement |
| `site.ad_pixels_running` | pixel Meta/Google/LinkedIn | measured | budget marketing attivo |
| `site.abandoned_blog` | blog con ultimo post > N mesi | estimated | content marketing trascurato |
| `site.open_roles` | careers/team page con ruoli | measured | hiring = budget + crescita |
| `site.no_https` / `site.broken_links` | header/link check | measured | competenza tecnica bassa |
| `site.missing_contact_form` | nessun form/solo telefono | estimated | lead capture debole |

### 3.2 Segnali aziendali/esterni (provider #39)

| Segnale | Fonte | Note |
|---|---|---|
| `company.recent_funding` | provider news/Crunchbase-like | trigger temporale forte |
| `company.new_location` | listing/OSM | espansione = bisogno ops |
| `company.hiring_role` | careers page / job board | Sellbird preset `hiring` |
| `company.negative_review_trend` | Google reviews provider | pain point |
| `person.job_change` | provider professionale | warm trigger |
| `mention.brand` | social/menzioni | inbound-adjacent |

### 3.3 Regole di qualità del segnale

- Ogni segnale porta `evidence_url`, `fetched_at`, `confidence`, `method`
  (regex/schema/http/dns/provider).
- `confidence < soglia` → non può da solo determinare `rank=hot`; solo in combinazione.
- Segnale stale oltre `max_age` (configurabile, default 30gg) → downgrade a hint.
- Nessun segnale inventato: se il check fallisce, si registra `error`, non `absent`.

## 4. Guardrails (P0, da #33/#36/#42)

1. **Fetch sicuro**: solo HTTP(S) pubblico; blocco private/loopback/link-local/
   metadata IP; validazione DNS+redirect a ogni hop; niente proxy env che bypassa.
   → #33.
2. **Consenso e provenienza**: ogni contatto/draft registra `source`,
   `consent_basis` (legitimate interest / consenso / pubbliato dal destinatario),
   `first_seen_at`. Regole GDPR/PECR/CASL per giurisdizione.
   → #36, check per-ricorrente stile Precleared opzionale.
3. **Suppression**: tabella hashed+workspace-scoped già esistente; estesa a
   opt-out conversazionale (una qualsiasi frase di opt-out = permanente,
   immediato, applicato anche a email già approvate — SendBird lo fa al gate).
   → #36.
4. **Quiet hours**: finestra di invio nella timezone del destinatario (fallback
   `usage.quiet_hours.timezone`), `weekdays_only`, cold email solo in finestra;
   reply mai trattenute dalla finestra. → #43.
5. **Pre-send gate deterministico** (no modello): merge tag, frasi vietate,
   lunghezza/link/HTML, deliverability destinatario, match destinatario↔record,
   link a domini non propri, telefono estraneo. → #43.
6. **Approval queue**: nessun invio automatico in M0–M2; `requires_approval`
   obbligatorio; `AiActionProposal` anti-replay/race/tardiva. → #42, #43.
7. **Bot disclosure**: se chiesto, risposta veritiera sull'automazione.
8. **DNC list**: `compliance.do_not_contact` per indirizzo/dominio con
   semantica chiara (dominio copre subdomini, indirizzo copre plus-tag).

## 5. Data model sketch (bozza, da raffinare in architecture)

Tabelle esistenti: `ai_prospects` (già in slice #32), tabella suppression hashed.

| Entità | Campi chiave | Note |
|---|---|---|
| `AiProspect` | workspace_id, identity_hash, domain, company_name, type, rank, status, raw_data json | esistente; estendere con `consent_basis`, `source`, `score_explain json` |
| `ai_prospect_observations` | prospect_id, signal_key, value, confidence, evidence_url, method, fetched_at, expires_at | #37/#40; immutabile, append-only |
| `ai_prospect_contacts` | prospect_id, kind (email/phone/social), value_hash, value_encrypted?, deliverability_status (verified/risky/guess/invalid), pattern_evidence, verified_at | mai email "inventata": `guess` non è deliverable |
| `ai_prospect_patterns` | domain, pattern_regex, evidence_contacts[], learned_at | apprendimento formato email stile Sellbird |
| `ai_prospect_drafts` | prospect_id/contact_id, subject, body, status (pending/approved/rejected/sent/held), held_reason, approved_by/at | legato a `AiActionProposal` |
| `ai_prospect_activities` | prospect_id, type (email_sent/reply_received/opt_out/meeting/...), payload json, actor | record-keeper equivalente |
| `ai_prospect_suppressions` | workspace_id, kind, value_hash, reason, source, created_at | esistente + motivo e revoca controllata |
| `ai_prospect_sequences` / `ai_prospect_touches` | draft_id, step, scheduled_at, status | follow-up cadence |
| `ai_prospect_conversations` | contact_id, stage (new/replied/qualified/meeting_booked/won/lost), summary, next_action_at | handoff a #34 |
| `ai_mailboxes` / `ai_mailbox_health` | provider, daily_limit, warmup state, bounce_rate, last_check | epic nuovo deliverability |
| `ai_booking_links` / `ai_meetings` | prospect_id, provider (cal.com/Calendly), external_id, starts_at | #34/new calendar |

Regole trasversali: `workspace_id` su ogni tabella (tenant isolation #40),
Pest su `.env.testing` SQLite, niente `RefreshDatabase`, cast in `casts()`,
`declare(strict_types=1)`.

## 6. Open questions

1. **Provider strategy per Discover**: quante fonti pubbliche (OSM, registro
   imprese, siti aggregatori) vs. API a pagamento (Apollo/PDL/Crustdata)? Serve
   un'interfaccia `DiscoveryProvider` con `--estimate` stile Sellbird?
   → legato a #39.
2. **Email verification**: verifier API esterno (ZeroBounce/NeverBounce/Emailable)
   vs. pattern+MX check locale? Budget e GDPR dei dati inviati al verifier?
   → nuovo epic.
3. **Mailbox sending**: modulo spedisce via Gmail API/M365/SMTP generico?
   Warmup: integrazione TrulyInbox o solo checklist DNS+`mailbox_health`?
   → nuovo epic.
4. **Conversazioni reply**: quanta autonomia per la bozza risposta? Stadi
   (new/replied/qualified/meeting/won/lost) allineati a quali stage CRM host?
   → #43/#34.
5. **Forecast**: formula da stadi pipeline (probabilità per stadio × valore) o
   semplice report? → #43.
6. **LinkedIn/social**: solo touchpoint manuale tracciato o automazione (ToS
   risk)? Default proposto: off, solo logging activity. → nuovo epic.
7. **Inbound lead capture**: form endpoint nel modulo o solo webhook
   normalizzato da fonti esterne? → #34.
8. **Export/CRM sync**: sola export CSV/JSON o sync bidirezionale con
   CRM host (LeadConnector-style)? → nuovo epic.
9. **ICP configuration**: weights per segnale in config modulo o per-workspace
   su tabella? → #37.
10. **Studio Sellbird licensing**: confermare che solo pattern/documentation
    ispirano il codice (AGPL) — nessun file vendored. Da verificare in review.

## 7. Fonti

- `/tmp/closer-skill/README.md` + skill dirs (prospector, follow-up, researcher,
  pipeline, scheduler, forecaster, win-back, record-keeper)
- `/tmp/sellbird/README.md`, `docs/signals-and-pipeline.md`,
  `docs/approval-and-sending.md`, `docs/deliverability.md`,
  `docs/inbound-leads.md`, `docs/social.md`, `docs/agent-app.md`
- Issue `laraxot/module_ai_fila5` #32–#43 (milestone P0/M1/M2/M3)
- `docs/prospecting.md`, `docs/bmad/prospecting-story.md` (slice shipped)
- Studio parallelo: `docs/sources/2026-11-27-closer-sellbird-study.md`
