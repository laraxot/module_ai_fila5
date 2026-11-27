---
title: "Acquisition stories — sprint status"
type: sprint-status
module: AI
tags: [bmad, ecc, prospecting, acquisition, stories, sprint]
created: 2026-11-27
updated: 2026-11-27
status: draft
qmd: "AI acquisition stories sprint status issue milestone"
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
  - acquisition-epics.md
  - prospecting-story.md
---

# Acquisition stories — indice sprint

Stati: `done` (slice verificata) · `in_progress` · `ready` (bloccanti risolti,
può partire) · `planned` (in attesa di dipendenze) · `new` (issue da creare).

"Parallel" = può avanzare in parallelo con le altre storie dello stesso set
(file/aree disgiunti).

## Slice shipped (baseline, #32)

| ID | Titolo | Issue | Milestone | Status | Dipendenze | Parallel |
|----|--------|-------|-----------|--------|------------|----------|
| ACQ-01 | Detect website signals + normalize prospect | #32 | M1 | done | — | — |
| ACQ-02 | Suppression hashed + persistence upsert | #32 | M1 | done | — | — |
| ACQ-03 | Rank wiring + brief + draft | #32 | M1 | done | ACQ-01/02 | — |
| ACQ-04 | CLI `ai:prospect-audit` | #32 | M1 | done | ACQ-03 | — |
| ACQ-05 | Filament read-only prospect queue | #32 | M2 | done | ACQ-02 | — |
| ACQ-06 | Pest slice prospecting (8 test/20 assert) | #32 | M2 | done | — | — |

## P0 — sicurezza e privacy

| ID | Titolo | Issue | Milestone | Status | Dipendenze | Parallel |
|----|--------|-------|-----------|--------|------------|----------|
| ACQ-10 | SSRF/redirect/DNS fail-closed nei site checks | #33 | P0 | planned | — | P0-set |
| ACQ-11 | Consenso, provenienza, soppressione, channel policy | #36 | P0 | planned | — | P0-set |
| ACQ-12 | AiActionProposal anti-replay/race/conferme | #42 | P0 | planned | — | P0-set |

## M1 — persistenza e import

| ID | Titolo | Issue | Milestone | Status | Dipendenze | Parallel |
|----|--------|-------|-----------|--------|------------|----------|
| ACQ-20 | AiProspect persistence/observations/identity/tenant isolation | #40 | M1 | planned | ACQ-11 (consent_basis) | M1-set |
| ACQ-21 | AiProspectPattern: apprendimento formato email per dominio | NEW-VERIFY-1 | M1 | new | ACQ-20 | M1-set |
| ACQ-22 | Fonti pubbliche + CSV import | #39 | M1 | planned | ACQ-20 | M1-set |
| ACQ-23 | API provider discovery (waterfall, --estimate) | #39 | M1 | planned | ACQ-20 | M1-set |
| ACQ-24 | Data quality: dedup, scoring fonte, freshness | #39 | M1 | planned | ACQ-20 | M1-set |

## M2 — segnali, UX e qualità

| ID | Titolo | Issue | Milestone | Status | Dipendenze | Parallel |
|----|--------|-------|-----------|--------|------------|----------|
| ACQ-30 | Segnali verificabili + evidence_url/fetched_at/confidence | #37 | M2 | planned | ACQ-10, ACQ-11, ACQ-20 | M2-set |
| ACQ-31 | ICP configurabile (weights per workspace) | #37 | M2 | planned | ACQ-30 | M2-set |
| ACQ-32 | Ranking spiegabile (score breakdown nel brief) | #37 | M2 | planned | ACQ-30, ACQ-31 | — |
| ACQ-33 | Filament inbox/pipeline/review queue (no send) | #38 | M2 | planned | ACQ-20, ACQ-32 | UX-set |
| ACQ-34 | Email verification waterfall adapter (verified/risky/guess/invalid) | NEW-VERIFY-2 | M2 | new | ACQ-21, ACQ-30 | M2-set |
| ACQ-35 | Decodifica obfuscation email nel crawler | NEW-VERIFY-3 | M2 | new | ACQ-10 | M2-set |
| ACQ-36 | CI + test sicurezza + verifiche modulo | #41 | M2 | planned | ACQ-10..35 | continuo |

## M3 — outreach, pipeline e inbound

| ID | Titolo | Issue | Milestone | Status | Dipendenze | Parallel |
|----|--------|-------|-----------|--------|------------|----------|
| ACQ-40 | Outreach core: pre-send gate deterministico | #43 | M3 | planned | ACQ-11, ACQ-12, ACQ-34 | M3-set |
| ACQ-41 | Follow-up sequence + stop conditions | #43 | M3 | planned | ACQ-40 | M3-set |
| ACQ-42 | Reply ingestion + stage machine base | #43 | M3 | planned | ACQ-40 | M3-set |
| ACQ-43 | Quiet hours TZ destinatario + daily limits | #43 | M3 | planned | ACQ-40 | M3-set |
| ACQ-44 | Forecast base da stadi pipeline | #43 | M3 | planned | ACQ-42 | — |
| ACQ-45 | Adapter 11 skill Closer | #35 | M3 | planned | ACQ-40..44 | docs/parallelo |
| ACQ-46 | Inbound lead capture (webhook/form) + consenso | #34 | M3 | planned | ACQ-11, NEW-INBOUND-1? | M3-set |
| ACQ-47 | Adapter CRM host + handoff booking base | #34 | M3 | planned | ACQ-42 | M3-set |
| ACQ-48 | Mailbox model + warmup ramp + bounce kill-switch | NEW-DELIV-1 | M3 | new | ACQ-43 | M3-set |
| ACQ-49 | DNS SPF/DKIM/DMARC check + TrulyInbox adapter opzionale | NEW-DELIV-2 | M3 | new | ACQ-48 | — |
| ACQ-50 | Send-gate: solo contatti deliverable | NEW-DELIV-3 | M3 | new | ACQ-34, ACQ-40 | bloccante send |
| ACQ-51 | Conversation stage machine completa | NEW-REPLY-1 | M3 | new | ACQ-42 | M3-set |
| ACQ-52 | Reply classifier + bozza risposta | NEW-REPLY-2 | M3 | new | ACQ-51 | M3-set |
| ACQ-53 | Opt-out immediato/permanente anche a draft approvati | NEW-REPLY-3 | M3 | new | ACQ-11, ACQ-52 | P0-in-M3 |
| ACQ-54 | Handoff escalation (legale/reclami/link sospetti) | NEW-REPLY-4 | M3 | new | ACQ-52 | M3-set |
| ACQ-55 | Booking link + AiMeeting da conferma | NEW-CAL-1 | M3 | new | ACQ-47 | M3-set |
| ACQ-56 | Slot proposal in bozza risposta | NEW-CAL-2 | M3 | new | ACQ-55 | — |
| ACQ-57 | Stage machine CRM completa + loss reasons | NEW-CRM-1 | M3 | new | ACQ-44, ACQ-51 | M3-set |
| ACQ-58 | Forecast avanzato + report Filament | NEW-CRM-2 | M3 | new | ACQ-57 | — |
| ACQ-59 | Win-back segment (Closer win-back) | NEW-CRM-3 | M3 | new | ACQ-57 | P3 |
| ACQ-60 | Social touchpoint manuale (activity) | NEW-SOCIAL-1 | M3 | new | ACQ-33 | M3-set |
| ACQ-61 | Social playbook draft + LinkedIn OFF default | NEW-SOCIAL-2 | M3 | new | ACQ-60 | P3 |
| ACQ-62 | Endpoint form inbound nel modulo | NEW-INBOUND-1 | M3 | new | ACQ-46 | open question |
| ACQ-63 | Export CSV/JSON + sync bidirezionale CRM | NEW-EXPORT-1 | M3 | new | ACQ-47, ACQ-57 | P2 |

## Note di pianificazione

- **Done-ish slice (#32)** copre ACQ-01..06: detect/normalize/suppress/rank/brief/
  draft/CLI/persistence base/Filament read-only/Pest. I "Next" residui di
  `prospecting-story.md` (workspace resolver host, ICP weights, provider
  discovery, quality gate host) sono riassorbiti in ACQ-20/22/23/30/31.
- **Blocco critico**: ACQ-30/34/40 non partono davvero prima di ACQ-10+ACQ-11+ACQ-12.
- **Prima cosa sbloccabile in parallelo**: ACQ-10, ACQ-11, ACQ-12 (P0-set) e
  ACQ-20/21/22 (M1-set, con caveat consenso in ACQ-11 per `consent_basis`).
- Issue NEW-* vanno creati su `laraxot/module_ai_fila5` con label coerenti
  (`[P1][DELIVERABILITY]`, `[P1][VERIFY]`, …) e milestone M1/M2/M3 come in
  `acquisition-epics.md`.
