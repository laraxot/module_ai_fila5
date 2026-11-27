---
title: "Acquisition epics — B2B client acquisition"
type: epic-map
module: AI
tags: [bmad, ecc, prospecting, acquisition, epics]
created: 2026-11-27
updated: 2026-11-27
status: draft
qmd: "AI acquisition epics stories milestone issue mapping"
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
  - acquisition-research.md
  - acquisition-stories.md
  - ../prospecting.md
---

# Acquisition epics — mappa issue → epic → milestone

Issue esistenti: `laraxot/module_ai_fila5` #32–#43.
Issue **nuovi proposti** (da creare, numeri assegnati da GitHub): indicati come
`NEW-*`. Milestone: **P0** = sicurezza e privacy · **M1** = persistenza e import ·
**M2** = segnali, UX e qualità · **M3** = outreach, pipeline e inbound.

## Epic E0 — Sicurezza e privacy (gate P0)

Goal: nessun fetch, consenso o invio reale prima che SSRF, provenance/consenso e
proposte azione siano blindati.

| Story | Issue | Note |
|---|---|---|
| SSRF/redirect/DNS fail-closed nei site checks | #33 | P0, blocca #37 |
| Consenso, provenienza, soppressione, channel policy | #36 | P0, fondamento di tutto |
| AiActionProposal anti-replay/race/conferme | #42 | P0, pre-requisito di qualsiasi send |

## Epic E1 — Persistenza e import (M1)

Goal: `AiProspect` affidabile multi-tenant e primo flusso di dati reali.

| Story | Issue | Note |
|---|---|---|
| Engine agnostico prospect (epic) | #32 | slice shipped: detect/normalize/suppress/rank/brief/draft/CLI |
| Persistenza, osservazioni, identità, tenant isolation | #40 | estende `ai_prospects` |
| Fonti pubbliche, CSV, API provider, data quality | #39 | waterfall provider stile Clay |

## Epic E2 — Segnali, UX e qualità (M2)

| Story | Issue | Note |
|---|---|---|
| Segnali verificabili + ICP configurabile + ranking spiegabile | #37 | catalogo in research §3 |
| Filament prospect inbox, pipeline CRM, review queue (no send) | #38 | XotBase/LangBase, read+review |
| CI, test sicurezza, verifiche modulo Prospecting | #41 | include test SSRF/consenso |

## Epic E3 — Outreach core (M3)

Goal: ciclo di vita post-qualificazione fino a reply e forecast, sempre gated.

| Story | Issue | Note |
|---|---|---|
| Nucleo ciclo outreach: gate invio IT, follow-up, risposte, stadi, forecast | #43 | deterministico, quiet hours, stages |
| Copertura 11 skill Closer in adapter agnostici | #35 | P2, mappa skill→adapter |
| Acquisizione inbound consensuale + adapter CRM + handoff booking | #34 | inbound + CRM read-side |

## Epic E4 — Deliverability e mailbox infra (NEW, M3)

Goal: dominio/inbox dedicati, warmup, limiti e health — senza questo il primo
invio reale brucia il dominio.

| Story | Issue | Priorità |
|---|---|---|
| Modello mailbox: provider (SMTP/Gmail/M365), daily limit, warmup ramp, bounce kill-switch | NEW-DELIV-1 | P1, M3 |
| DNS check SPF/DKIM/DMARC + dominio dedicato checklist + TrulyInbox-style warmup adapter (opzionale) | NEW-DELIV-2 | P1, M3 |
| Pre-send integrazione: deliverability status contatto obbligatorio (solo `verified`) | NEW-DELIV-3 | P0-blocker con #43 |

## Epic E5 — Verifica contatti (NEW, M1→M2)

Goal: nessun indirizzo "inventato" inviato; solo `verified`/`risky`-gestiti.

| Story | Issue | Priorità |
|---|---|---|
| `AiProspectPattern` per dominio + apprendimento da indirizzi pubblicati (style Sellbird) | NEW-VERIFY-1 | P1, M1 |
| Adapter verifica waterfall: MX check locale → provider esterno (configurabile) → tagging verified/risky/guess/invalid | NEW-VERIFY-2 | P1, M2 |
| Decodifica obfuscation email (Cloudflare protection, `[at]`, `data-user`) nel crawler profilo | NEW-VERIFY-3 | P2, M2 |

## Epic E6 — Reply agent e conversazioni (NEW, M3)

Goal: agent che classifica risposte, bozza risposte, applica opt-out e passa il
pallino agli umani — stadi conversazione allineati a #43.

| Story | Issue | Priorità |
|---|---|---|
| `AiProspectConversation` + stage machine (new/replied/qualified/meeting/won/lost) + activity log | NEW-REPLY-1 | P1, M3 (parziale in #43) |
| Reply classifier + bozza risposta con stesse regole opt-out multilingua (EN/IT/ES/FR/DE minimo) | NEW-REPLY-2 | P1, M3 |
| Opt-out immediato e permanente anche a draft già approvati (send-gate check) | NEW-REPLY-3 | P0, dipende #36 |
| Handoff umano: escalation automatica per minacce legali, reclami, link sospetti | NEW-REPLY-4 | P1, M3 |

## Epic E7 — Calendar e booking (NEW, M3)

Goal: dal "sì" alla call senza uscire dal flusso.

| Story | Issue | Priorità |
|---|---|---|
| Integrazione booking link (cal.com/Calendly generico) + creazione `AiMeeting` da conferma | NEW-CAL-1 | P1, M3 (handoff base in #34) |
| Rilevamento disponibilità e proposta slot nella bozza risposta | NEW-CAL-2 | P2, M3 |

## Epic E8 — Pipeline CRM avanzata e forecasting (NEW, M3)

Goal: stadi commerciali completi e previsione, oltre il minimo di #43.

| Story | Issue | Priorità |
|---|---|---|
| Stage machine CRM completa + transizioni + motivi di perdita | NEW-CRM-1 | P1, M3 (parziale #43/#38) |
| Forecast: probabilità per stadio × valore opportunità, report Filament | NEW-CRM-2 | P2, M3 (parziale #43) |
| Win-back segment: prospect dormienti per ultimo contatto | NEW-CRM-3 | P3, M3 (Closer win-back) |

## Epic E9 — LinkedIn e social touchpoint (NEW, M3)

Goal: touchpoint social tracciati, automazione minima e opzionale.

| Story | Issue | Priorità |
|---|---|---|
| `channel=social` come activity/touch manuale con evidenza e data | NEW-SOCIAL-1 | P2, M3 |
| Playbook social (post/connessione) come draft approvabile; LinkedIn automazione OFF di default (ToS) | NEW-SOCIAL-2 | P3, M3 |

## Epic E10 — Inbound e sync (parziale esistente)

| Story | Issue | Priorità |
|---|---|---|
| Lead inbound da form/webhook normalizzato + consenso esplicito | #34 | P1, M3 |
| Adapter CRM host (read/write stage, contatti) | #34 | P1, M3 |
| Endpoint form pubblicabile nel modulo (o solo webhook in?) — **open question** | NEW-INBOUND-1 | P2, M3 |
| Export CSV/JSON sequencer-ready + sync bidirezionale CRM | NEW-EXPORT-1 | P2, M3 |

## Ordine consigliato (dipendenze)

1. **P0**: #33, #36, #42 — parallelizzabili tra loro.
2. **M1**: #40 + NEW-VERIFY-1 (pattern) → poi #39, #32 remainder.
3. **M2**: #37 (dopo #33+#36), #38, #41, NEW-VERIFY-2/3.
4. **M3**: #43 → #34 → NEW-DELIV-*, NEW-REPLY-*, NEW-CAL-*, #35, NEW-CRM-*, NEW-SOCIAL-*, NEW-INBOUND-1, NEW-EXPORT-1.

Regola: nessun NEW-REPLY/NEW-DELIV merge prima di #36+#42+#43 gate stabilizzati.
