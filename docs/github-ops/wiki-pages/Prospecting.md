# Prospecting — pipeline acquisizione B2B

Epic: issue **#32** «Motore agnostico di ricerca e qualificazione prospect B2B (Closer + Sellbird)»,
etichetta `track-prospecting`, milestone **M1–M3**. Documentazione canonica: `docs/prospecting.md`,
story: `docs/bmad/prospecting-story.md`.

## Fasi target

`discover → profile → enrich → verify → write → send → reply → book → forecast`

Il modulo è **agnostico**: accetta dati già acquisiti da fonti pubbliche; gli adapter
provider-specifici (scoperta, verifica email, invio) sono responsabilità dell'app host.

## Slice shipped (issue #32, 2026-11-27)

- [x] `AiProspect` + migrazione additiva con workspace isolation e identità deterministica (`public_id`)
- [x] `AiProspectSignal` — segnali con source, confidence, score e breakdown spiegabile
- [x] `AiProspectDraft` — bozze outreach con `lawful_basis`, `personalization`, review workflow
- [x] `AiSuppression` — soppressione hashed, workspace-scoped, on re-import e draft
- [x] Actions: `DetectWebsiteSignalsAction`, `NormalizeProspectImportAction`,
  `ComputeProspectSignalsAction`, `RankProspectLeadsAction`, `BuildProspectBriefAction`,
  `DraftProspectOutreachAction`, `ApplyProspectSuppressionAction`, `ImportProspectsAction`,
  `UpdateProspectStatusAction`, `SuppressProspectAction`
- [x] CLI `php artisan ai:prospect-audit <url> --type=business`
- [x] Filament read-only `AiProspectResource` (coda review admin, nessun invio)
- [x] Pest: 8 test / 20 assertion sullo slice prospect

## Mappa issue → fase

Riferimento generico «issues #32+» (le issue figlie possono crescere):

| Issue | Tema | Fase / milestone |
|---|---|---|
| #32 | Epic motore prospecting | M1–M3 |
| #33 | [P0][SECURITY] SSRF, redirect/DNS privati, falsi score | verify — M1 |
| #36 | [P0][PRIVACY] consenso, provenienza, soppressione, policy canali | trasversale — M1 |
| #40 | [P1][DATA] persistenza AiProspect, osservazioni, tenant isolation | persist — M1 |
| #39 | [P1][IMPORT] sorgenti pubbliche, CSV, API provider | discover/enrich — M1 |
| #37 | [P1][EVIDENCE] segnali verificabili, ICP configurabile, ranking spiegabile | profile/verify — M2 |
| #38 | [P1][UX] Filament inbox, pipeline CRM, coda review | UX — M2 |
| #41 | [P1][QUALITY] CI, test sicurezza, verifiche modulo | quality — M2 |
| #34 | [P1][INBOUND] acquisizione consensuale, adapter CRM, handoff | inbound — M3 |
| #35 | [P2][SALES-OPS] copertura 11 skill Closer in adapter | sales-ops — M3 |
| #42 | [P0][SECURITY] AiActionProposal: replay, race, conferme | sicurezza azioni — M1 |
| #43 | [P1][OUTREACH] ciclo di vita outreach: gate invio IT, follow-up, stadi, forecast | send→forecast — M3 |

## Milestone

- **M1 — persistenza e import**: #33, #36, #40, #39 (+ sicurezza #42)
- **M2 — segnali, UX e qualità**: #37, #38, #41
- **M3 — outreach, pipeline e inbound**: #34, #35, #43
- Milestone separata **«P0 - sicurezza e privacy»** raggruppa i gate bloccanti.

## Regole non negoziabili (ECC)

1. Solo HTML pubblico; bloccare host privati/localhost (base difesa SSRF, issue #33)
2. Mai inventare email personali
3. **Nessun invio automatico** — solo draft, invio solo dopo review umana
4. I segnali sono hint salvo `confidence=measured|error`
5. La soppressione è onorata su re-import e generazione draft

## Cosa manca (host integration)

- ICP config per i pesi dei segnali
- Workspace resolver e policy di autorizzazione dell'app host
- Adapter discovery provider-specifici (OSM, listing, CSV già parzialmente via `source`)
- Verifica email reale, send/reply/book/forecast (M3)
