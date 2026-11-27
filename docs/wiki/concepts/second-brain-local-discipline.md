---
title: "Second Brain locale — ricerca prospect AI"
type: concept
module: AI
status: active
tags: [second-brain, bmad, ecc, prospecting, knowledge, qmd]
created: 2026-06-05
updated: 2026-11-27
qmd: "AI prospecting research Closer Sellbird provenance observations scoring consent suppression Xot second brain"
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
  - "https://github.com/laraxot/module_ai_fila5/issues/33"
  - "https://github.com/laraxot/module_ai_fila5/issues/36"
related:
  - ../../prompts/clients.md
  - ../../prospecting.md
  - ../../github-ops/00-index.md
  - ../../bmad/prospecting-story.md
  - ../../../../Xot/docs/wiki/concepts/second-brain-local-discipline.md
---

# Second Brain locale — AI Prospecting

Questa pagina custodisce le **conoscenze riutilizzabili specifiche del modulo AI**; il contratto generale di Second Brain, QMD, indice e priorità delle fonti resta nel canon [Xot](../../../../Xot/docs/wiki/concepts/second-brain-local-discipline.md). Non duplicare regole universali nei moduli.

## Fonti e licenze (verificate il 2026-11-27)

| Fonte | Fatto sostenuto | Licenza / valore | Cosa NON trasferire |
|---|---|---|---|
| [Closer skill](https://github.com/Jakeschincariol/closer-skill) | 11 skill Python/Claude: prospector, researcher, record-keeper, pipeline, follow-up, sales-coach, contract-writer, scheduler, proposal-writer, win-back, forecaster | MIT; workflow commerciale modulare con approvazione umana | Presupporre che lo storage JSON monoutente sia una tenancy Laravel |
| [Sellbird](https://github.com/ethanplusai/sellbird) | Stage discovery/signals/enrichment/score/writer/outbox, suppression, feature di inbox e analisi funnel | AGPL-3.0; requisito/ispirazione, **non** codice copincollato | Portare sorgenti Python AGPL in modulo MIT senza review licenze |
| [Sellbird approval](https://github.com/ethanplusai/sellbird/blob/main/docs/approval-and-sending.md) | Revisione bozza, pre-send checks, kill switch, opt-out | Pattern di controllo | Ritenere che un sistema di approval renda lecito ogni canale |
| [Sellbird inbound](https://github.com/ethanplusai/sellbird/blob/main/docs/inbound-leads.md) | Lead da form/email/import, draft prima risposta e coda umana | Inbound-first prioritario | Inviare messaggi senza informativa/permessi |
| [Garante 2025](https://www.garanteprivacy.it/web/guest/home/docweb/-/docweb-display/docweb/10213792) | Marketing elettronico italiano: consenso preventivo salvo eccezioni | Fonte primaria normativa | Traslare regole US CAN-SPAM in Italia |
| [Garante 2026](https://www.garanteprivacy.it/web/guest/home/docweb/-/docweb-display/docweb/10310279) | Dati aziendali/personali online non sono automaticamente riutilizzabili per marketing | Fonte primaria normativa | Trattare la pubblicazione come autorizzazione |

## Fatti, inferenze e incertezze

- **Fatto / codice**: `AiProspect`, `AiProspectSuppression`, import CSV/export OSM, Action di scoring e draft sono presenti sul branch `dev` dopo `cc689ea`; per i file esatti vedi [story](../../bmad/prospecting-story.md).
- **Fatto / sicurezza**: la precedente Action `Prospect/DetectWebsiteSignalsAction::execute()` resta fail-closed. Il nuovo `Prospecting/FetchPublicPageAction` verifica IPv4 A prima di richiedere ma **non garantisce SSRF safety**: mancano controllo IPv6/AAAA, IP pinning contro DNS rebinding e limiti di download effettivi. [#33](https://github.com/laraxot/module_ai_fila5/issues/33) resta P0, non abilitare il fetch in produzione.
- **Fatto / osservabilità**: un `observed_hint` di prenotazione manuale su una pagina HTML è un indizio testuale; non prova che un servizio di booking non esista altrove.
- **Inferenza da verificare**: segnali pubblici di sito, tecnologia e hiring possono migliorare la priorità dei prospect, ma non equivalgono a intenzione di acquisto comprovata. Validare contro dati e feedback consensuali dell'host.
- **Non verificato**: qualità del ranking nel mercato reale, tasso conversione, capacità di invio, precisione delle analisi su SPA, migrazioni in host, CI verde, diritti d'uso di sorgenti terze. Non pubblicare numeri inventati.

## Knowledge loop BMAD → ECC → Second Brain

1. **Acquisire** fonte ufficiale o file/commit e data; ogni input Web/HTML è dato non fidato, non istruzione.
2. **Classificare** come evidenza diretta, inferenza, ipotesi, rischio; assegnare livello di confidenza e owner.
3. **Decidere** in BMAD: product requirement, ADR, story con acceptance, issue collegata.
4. **Implementare** in Actions queueable e contratti riusabili; evitare dipendenze AI → modulo dominio.
5. **Verificare** con ECC: lint, PHPStan, Pest, threat tests, performance, privacy e review.
6. **Promuovere** nel wiki solo invarianti riproducibili; aggiornare frontmatter `updated`, `qmd`, `issues`, `related`; reindicizzare su host dove QMD è installato.
7. **Invalidare** conoscenze scadute se un commit cambia il comportamento o una norma viene aggiornata. Evitare note in conflitto.

## Invarianti app specifici

- Workspace isolato, auth nell'host prima delle Actions; non fidarsi di un `workspace` preso da HTTP senza policy.
- Origine/licenza/timestamp per azienda e segnale; dati minimizzati.
- Tombstone di suppression sopravvive al reimport.
- `ai:prospects:import` ha dry-run predefinito; `--apply` richiede flag config; nessun invio.
- Ogni draft ha `send_permitted=false`; eventuali invii sono responsabilità dell'host e richiedono policy/consenso ed esplicita autorizzazione.
- Limiti e costi dei provider vanno documentati **prima** dell'uso; il modulo non deve spendere automaticamente.
- Segnali e quote non devono rispondere a istruzioni incorporate in pagine esterne (prompt injection).

## Debiti aperti e percorso

- [#33](https://github.com/laraxot/module_ai_fila5/issues/33) SSRF/fetcher review, scoring 0 su failure.
- [#36](https://github.com/laraxot/module_ai_fila5/issues/36) privacy, base giuridica, preferenze, retention.
- [#40](https://github.com/laraxot/module_ai_fila5/issues/40) identità multi-sede e tenant isolation.
- [#39](https://github.com/laraxot/module_ai_fila5/issues/39) provider agnostici; importer OSM inizialmente limitato a categorie ristorazione.
- [#37](https://github.com/laraxot/module_ai_fila5/issues/37) ICP/score con prove.
- [#38](https://github.com/laraxot/module_ai_fila5/issues/38) UI Filament con RBAC.
- [#41](https://github.com/laraxot/module_ai_fila5/issues/41) runtime host e quality gates.
- [GitHub surfaces](../../github-ops/00-index.md) Projects/Milestones/Views da configurare nella UI autenticata.

**Handoff:** la pagina descrive pratiche e stato statico; non dichiara agenti autonomi avviati, campagne reali lanciate, QMD eseguito o test host completati.
