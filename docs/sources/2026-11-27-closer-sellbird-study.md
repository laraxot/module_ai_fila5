---
title: "Studio closer-skill + sellbird — pattern riusabili per la prospecting pipeline"
type: note
module: AI
tags: [bmad, ecc, prospecting, closer-skill, sellbird, outbox, signals, cohorts, gate, suppressione]
created: 2026-11-27
updated: 2026-11-27
qmd: "AI prospecting studio closer sellbird signals cohort gate soppressione follow-up win-back forecast"
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
discussions: []
---

# Studio: closer-skill e sellbird → modulo AI

Nota di studio delle due repo di riferimento (`/tmp/closer-skill`, `/tmp/sellbird`),
mappata sulle primitive già presenti nel modulo AI. **Solo pattern e idee**:
`closer-skill` è MIT, `sellbird` è **AGPL-3.0** — niente codice vendored, niente
copia testuale di prompt o regex.

## 1. I due progetti

### closer-skill (MIT)

Plugin Claude Code con 11 skill vendibili come "CRM in un JSON file"
(`/tmp/closer-skill/README.md`). Ogni skill è `SKILL.md` + un helper Python
stdlib-only che legge/scrive `closer/pipeline.json`. Nessun invio autonomo:
ogni messaggio passa da un **yes esplicito dell'utente**, word-for-word
(`pipeline/deals.py:67-82` — `parse_yes`: solo "yes"/"y" come prima parola,
mai "ok", mai "yes to all", mai una domanda).

| Skill | Cosa fa | File chiave |
|---|---|---|
| record-keeper | sincronizza inbox → attività nel CRM | `record-keeper/log.py` |
| prospector | find + audit gap + draft opening | `prospector/leads.py` |
| researcher | brief pre-call da pagine pubbliche | `researcher/brief.py` |
| follow-up | cadence fino a booking, poi stop | `follow-up/followup.py` |
| pipeline | board + 8 stage + suppression | `pipeline/deals.py` |
| proposal-writer | proposta con 1-3 opzioni prezzate | `proposal-writer/proposal.py` |
| contract-writer | contratto same-day dopo il sì | `contract-writer/contract.py` |
| sales-coach | card objection durante la call | `sales-coach/coach.py` |
| scheduler | parse "giovedì alle 2" → invite | `scheduler/slots.py` |
| win-back | riprova perdite dopo 60+ giorni | `win-back/winback.py` |
| forecaster | commit/likely/best-case pesato | `forecaster/forecast.py` |

### sellbird (AGPL-3.0, ex-Harvey)

Piattaforma outreach autonoma ~28k righe Python (`/tmp/sellbird/sellbird/`):
loop heartbeat ogni 15 min (`main.py`), 5 agenti (`agents/scout.py`,
`writer.py`, `sender.py`, `handler.py`, `analyst.py`), collector HTTP gratuiti
(`collectors/discover.py`, `profile.py`, `people.py`), DB SQLite come asset
(`state.py`, 3660 righe), dashboard FastAPI, libreria `skills/*.md` di
playbook vendibili come markdown editabile.

Filosofia dichiarata (`docs/how-it-works.md:79-91`): "autonomous doesn't mean
unsupervised"; "if it can be gotten deterministically, don't use a model";
"a failure is an observation, not silence"; "the database is the asset".

## 2. Pattern riutilizzabili → modulo AI

### 2.1 Stages DISCOVER → PROFILE → ENRICH → VERIFY → WRITE → SEND → REPLY → BOOK → FORECAST

Sellbird concatena stage **via query sul DB, non via hand-off**
(`sellbird/pipeline.py:1-16`): PROFILE chiede "quali aziende non ho guardato o
sono stale (>90 gg)" (`state.py:2868`, `PROFILE_STALE_DAYS=90`). Ogni stage è
indipendentemente ri-eseguibile: profilo si rifà senza ri-comprare discovery.
Mappatura:

| Stage | Sellbird | Closer | Modulo AI oggi |
|---|---|---|---|
| DISCOVER | `collectors/discover.py` (OSM/Serper/DataForSEO, junk filter per pattern `JUNK_DOMAIN_PATTERNS`/`JUNK_NAME_PATTERNS` `:53,113`) | search listing manuali → CSV | `ImportProspectsAction` (input già acquisito) |
| PROFILE | `collectors/profile.py` — 3 HTTP req, regex su HTML già in mano | `leads.py audit` — 1 pagina, regex gap | `DetectWebsiteSignalsAction` |
| ENRICH | `collectors/people.py` — owner da card/sentenze/schema.org, max 2 persone | `researcher/brief.py` | `BuildProspectBriefAction` (solo testo) |
| VERIFY | `integrations/email_finder.py` + `email_patterns` table | mai verificato | enum `EMAIL_VERIFICATION` esiste, nessuna action |
| WRITE | `agents/writer.py` per-prospect grounded facts | `leads.py draft` template+footer | `DraftProspectOutreachAction` |
| SEND | `agents/sender.py` outbox + gate | Gmail draft su yes | draft only, nessun sender |
| REPLY | `agents/handler.py` intent classification | `record-keeper` sync inbox | assente |
| BOOK | `booking.py` + `meetings` table | `scheduler/slots.py` | assente |
| FORECAST | `deals.py` + stage events | `forecaster/forecast.py` | assente |

### 2.2 Signal catalog + conferma umana

`sellbird/signals.py:20+` definisce **29 segnali** in un catalogo dichiarante:
`code`, `label`, `description`, `category` (discovery/profile/people/
verification), `value_type` (num/text/bool), `collector`, `cost_note`,
`confidence_floor`. Regola: **niente viene raccolto finché l'umano non
conferma il segnale**; solo `confirmed` si raccolgono
(`docs/signals-and-pipeline.md:7-15`). Ogni segnale è una **osservazione**
(`observations` table, `state.py:416`), mai una colonna: migrazione gratuita,
storia gratis, confidenza+provenance viaggiano col fatto.

Gap-check più leggero di closer (`prospector/leads.py:55-65`, `GAPS`):
`no-booking`(3), `not-mobile`(3), `slow`(2), `no-ads`(2), `no-ordering`(2),
`no-reviews`(1), `no-site`(4) — score pesati, gating per business type
(`NEEDS_BOOKING`/`NEEDS_ORDERING` regex su tipo).

### 2.3 Cohort = query, non lista

`state.py:2813 cohort_companies`: `require` = tutti i segnali presenti
(HAVING COUNT DISTINCT = N), `exclude` = NOT IN, `ranges` = bounds numerici
(es. `SERP_RANK` 11–30). Conta live e pagina. Il cohort è "una query con una
ragione dietro", non una lista nera.

### 2.4 Deterministico dove possibile

Regola esplicita (`docs/how-it-works.md:85`): rank, tech detection, junk
filter, priorità heartbeat, pre-send gate = codice puro. Modello solo per
judgment (score personalizzazione in `prompts/scout.md`, copy in
`prompts/writer.md`, intent in `prompts/handler.md`). Priorità heartbeat
deterministica (`main.py:154-209`): `send > write > prospect > idle`, mai una
chiamata modello per decidere.

### 2.5 Pre-send gate deterministico

`sellbird/gate.py:170-243 pre_send_check` — zero modello, ultimo controllo
prima dell'invio: regex email destinatario, DNC list (con normalizzazione
NFKC, IDNA, subdomain boundary `:114-142`), match prospect↔record, email_status
deliverable, subject ≤90 char, body ≤220 parole, merge tag non renderizzati,
frasi vietate (`BANNED_PHRASES:17-28` — spam + AI-tells), max 1 link, footer
postale CAN-SPAM, niente HTML. Autopilot aggiunge `autopilot_review` (`:246+`):
link a domini non tuoi, numeri di telefono estranei, richieste di denaro,
quote di prompt/skill — tutto torna all'outbox "Held".

### 2.6 Approval-by-default / outbox

`sellbird/docs/approval-and-sending.md`: default **ogni email aspetta
approvazione**; outbox è "decision desk" una-mail-per-pannello (niente
approve-all wall). Autopilot opzionale (`require_approval: false`) ma le mail
non approvate passano comunque `autopilot_review`. Closer è più estremo:
**mai** invio, solo Gmail draft dopo yes word-for-word.

### 2.7 Soppressione / opt-out permanente

- Sellbird: opt-out immediato e permanente ovunque nella reply, anche bare
  "STOP" (`consent.py:84-87`); `do_not_contact` list controllata a ogni send
  e in export; "nothing moves someone off opted out".
- Closer: `suppression` array nel JSON; `followup.py stop` marca
  do-not-contact per sempre; `leads.py add` skippa suppressed
  (`leads.py:156-158`).
- Modulo AI ha già `AiSuppression` + `AiSuppressionReasonEnum`
  (do_not_contact, unsubscribed, bounced, invalid_email, gdpr_request) con
  `email_hash` e `public_id` deterministico — coerente.

### 2.8 Quiet hours + send window

`sellbird/main.py:70-152`: quiet hours (default 21:00–08:00) fermano
prospecting/writing/replies ma **non** cold email se `send_window` consente —
`send_window.py` calcola il fuso dal nazione/provincia/coordinate del prospect
e schedule per destinatario. `consent.py:103-116` per SMS/voice: stesso
concetto, decisione mai cachata ("a STOP that arrived a second ago counts").

### 2.9 Address-pattern learning + verification tiers

`docs/signals-and-pipeline.md:150-154` + `integrations/email_finder.py`:
impara il pattern per dominio dalle mail pubblicate (`nvale@` →
`{first initial}{last}`), decodifica Cloudflare email protection e
`data-user`/`data-domain`, non inventa mai, cache `email_patterns` per
dominio. Tag onesti: `verified` / `risky` (catch-all) / `guess` / `invalid`.
**Solo deliverable si inviano**; guess mai trattata come win; con zero
evidenza assume `first.last@` o `first@` per small business.

### 2.10 Follow-up cadence

`follow-up/followup.py:17-24,40-43`: max **3 follow-up** dopo l'opening, gap
(2,2,4) giorni con weekend→lunedì, stop a 30 giorni di silenzio, stop al primo
"no", stop al booking, "your turn" se hanno risposto. Writer.md di sellbird:
email 2 = nuovo proof point, email 3 = breakup, niente guilt.

### 2.11 Win-back

`win-back/winback.py:14-20`: lost con reason timing/budget/undecided dopo
**60 giorni** (competitor 180), mai dopo unsubscribe/no/DNC, **1 win-back per
deal ogni 90 giorni**, mai un secondo. Reason categorizzate via regex.

### 2.12 Forecasting stage-weighted

`forecaster/forecast.py:20-24`: `STAGE_CHANCE` lead .05 → contacted .10 →
replied .20 → call-booked .30 → proposal .50 → contract-sent .80 → won 1.0.
Commit = contract-sent del mese; Likely = somma pesata; Best case = tutto.
Con ≥10 deal chiusi propri, scale al win rate reale (`MIN_HISTORY=10`).
Unsubscribe da cold email **non** conta come lost sale.

## 3. Gap analysis: modulo AI vs repo vs issue #32-42

### Ha già

- `AiProspect` + `public_id=sha256(domain)` + workspace isolation
  (`app/Models/AiProspect.php`) — identità deterministica come `companies.domain`
  di sellbird.
- `AiProspectSignal` (fonte, confidenza, score, breakdown) — embriona già la
  filosofia "osservazione, non colonna", ma è legata al prospect, non
  condivisa come catalogo.
- `DetectWebsiteSignalsAction` — PROFILE deterministico con SSRF guard
  (`normalizeUrl` blocca localhost/private), signal `confidence`
  hint/measured/error, industry-aware booking/commerce.
- `RankProspectLeadsAction` — rank + suppression filter.
- `BuildProspectBriefAction` — brief con talking points e domande.
- `DraftProspectOutreachAction` — draft only, `requires_approval: true`,
  footer CAN-SPAM con opt-out, `daily_cap_hint: 50`, `[fill:]` placeholders.
- `AiProspectDraft` con stati draft→pending_review→reviewed→sent→suppressed
  e `lawful_basis`.
- `AiSuppression` persistente, workspace-scoped, sopravvive ai re-import.
- Filament read-only `AiProspectResource` + `AiProspectsTable`.
- `XotBaseAgent` + `McpToolAdapter` con approval policy → outbox AG-UI già
  possibile.

### Manca (gap vs sellbird/closer)

1. **Catalogo segnali dichiarato e confermato** — oggi i segnali sono hardcoded
   in `DetectWebsiteSignalsAction::analyze`; manca tabella `signal_codes` +
   flag `confirmed` + UI di review (issue #33-34 probabili).
2. **DISCOVER** — nessun adapter di discovery (OSM/Serper/listing); il modulo
   accetta input già acquisito.
3. **ENRICH persone** — nessun parser di team page / owner detection
   (`people.py`); `AiProspect` non ha contatti (solo company).
4. **VERIFY email** — nessun `email_patterns` learning, nessun verifier,
   nessun tier verified/risky/guess/invalid.
5. **Outbox + pre-send gate** — `AiProspectDraft` esiste ma non c'è
   `pre_send_check` deterministico (merge tags, banned phrases, link budget,
   DNC check, email_status) né coda di invio.
6. **REPLY/BOOK** — nessun handler di intent, nessuna tabella conversazioni
   prospect, nessun booking.
7. **Follow-up cadence** — nessuna regola 3-max/30gg/stop-on-no.
8. **Win-back** — `COLD_LOST` esiste ma senza reason né cooldown.
9. **Forecast** — nessuno stage-weighted.
10. **Quiet hours / send window** — config assente.
11. **Cohort builder** — `AiProspectSignal` c'è ma nessuna query
    require/exclude/range tipo `cohort_companies`.
12. **Heartbeat/decision layer** — nessun scheduler che decide
    send>write>prospect>idle; le Actions sono solo callable.

## 4. Raccomandazioni per Laravel/Filament/XotBase

Ordine suggerito (coerente con issue #32-42):

1. **`ai_signal_catalog` + `confirmed` flag** — migrazione tabella con
   code/label/category/value_type/cost_note/confidence_floor; Action
   `ConfirmSignalCatalogAction`; Filament page/tab "Signals" con toggle.
   Pattern: `signals.py` + `dashboard.py:4380`.
2. **Osservazioni come righe** — estendere `AiProspectSignal` o aggiungere
   `ai_signal_observations` con `observed_at`, `run_id`, `confidence`,
   `value_num/value_text`, così history e staleness (90gg) sono gratis.
3. **Cohort come query Filament** — `AiProspectResource` filter avanzato o
   `CohortBuilderAction` che traduce require/exclude/ranges in Eloquent
   (`whereHas` + `havingRaw`), con contatore live.
4. **`PreSendGateAction`** — portare i check di `gate.py` in PHP: regex merge
   tag, lista banned phrases (config, non hardcoded), link count, DNC match
   su `AiSuppression` (attenzione: sellbird matcha domain+subdomain e
   plus-tags — replicare `do_not_contact_match`), `email_status` tier,
   footer postale. Deterministico, zero LLM.
5. **`AiProspectContact` model** — persona (nome, titolo, email, email_status,
   timezone) sotto `AiProspect`, max 2/company come sellbird.
6. **`AiProspectOutbox` model** — una riga per (prospect, step, scheduled_at),
   stati pending_review/approved/held/sent; Action `DrainOutboxJob` con
   jitter, daily cap, warmup ramp, quiet hours.
7. **Quiet hours/send window** — config `ai.prospecting.send_window`
   (start/end/timezone fallback); Job ricontrolla `recipient_zone` prima del
   send; mai cachare la decisione.
8. **Follow-up engine** — `FollowUpCadenceAction` con costanti
   `MAX=3, GAPS=[2,2,4], SILENCE=30gg`; stop rules come stati/prospect
   (replied→your turn, booked→stop, lost→win-back pool).
9. **Win-back** — `WinBackAction` con `LOST_WAIT=60gg`, `COMPETITOR_WAIT=180gg`,
   `COOLDOWN=90gg`, regex sulle `lost_reason`.
10. **Forecast** — `ForecastAction` con `STAGE_CHANCE` mappato su
    `AiProspectStatusEnum` (estendere enum: replied, call_booked, proposal,
    contract_sent) + scale su win rate reale dopo 10 chiusure.
11. **Agent Neuron** — `ProspectAgent extends XotBaseAgent` con tool
    `McpToolAdapter` su Action tipo `ListCohortTool`, `DraftOutreachTool`
    (con `withApprovalPolicy`), `SuppressTool`; gli skill markdown di
    sellbird (`skills/*.md`) diventano `docs/prompts/` del modulo.
12. **Filament** — estendere `AiProspectResource` (oggi read-only) con
   drawer segnali, tab Outbox stile "decision desk" (una mail per volta,
   approva/rifiuta), e pagina Signals per la conferma catalogo.
13   **Discover adapter** — primo adapter OSM/Overpass o Serper dietro
   `DiscoveryProviderInterface`, con `estimate()` prima di spendere
   (pattern `sellbird/collectors/discover.py`).

## 5. Note di licenza e attendibilità

- closer-skill MIT: pattern copiabili, ma il valore reale è nel
  `pipeline.json` schema + `parse_yes` — già recepiti in `AiProspect*`.
- sellbird AGPL-3.0: **solo idee**. In particolare non copiare regex
  specifiche (gate `_KNOWN_ENDINGS`, `BANNED_PHRASES`, pattern YC/accelerator)
  testuali; riscrivere equivalenti in PHP con commento di provenienza.
- Sellbird budgeta il modello via abbonamento CLI isolato
  (`claude_runtime.py`); nel modulo AI il fornitore è
  `ResolveAgentProviderAction` (Groq/OpenRouter) — il pattern rilevante è
  "modello solo per judgment", non il runtime.
