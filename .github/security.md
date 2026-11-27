---
title: "Security Policy — Laraxot AI Module"
type: policy
module: AI
status: active
created: 2026-09-26
updated: 2026-11-27
qmd: "module AI security policy disclosure SSRF prompt injection privacy multi tenancy"
tags: [security, responsible-disclosure, ecc, ai]
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/33"
  - "https://github.com/laraxot/module_ai_fila5/issues/36"
  - "https://github.com/laraxot/module_ai_fila5/issues/42"
---

# Security Policy

Il modulo AI è usato da diversi progetti Laravel/Laraxot. La sicurezza di provider, strumenti AI, dati personali e tenancy ha priorità sul rilascio di nuove funzionalità.

## Segnalazione responsabile

**Non pubblicare exploit attivi, credenziali, email private, prompt con segreti, dati di prospect reali o informazioni che facilitino un attacco in GitHub Issues o Discussions.**

Per segnalazioni riservate usa, se attivato sul repository, [Private vulnerability reporting](https://github.com/laraxot/module_ai_fila5/security/advisories/new). In alternativa contatta il maintainer all'indirizzo già pubblicato: [marco.sottana@gmail.com](mailto:marco.sottana@gmail.com).

Fornisci quando possibile: versione/commit, file interessati, gravità e impatto, riproduzione minimale **senza accedere a dati di terzi**, mitigazione proposta e possibilità di contatto. Non sfruttare vulnerabilità su sistemi di produzione, non effettuare scansioni aggressive e non inserire credenziali reali nella prova.

## Versioni supportate

L'attività attiva del modulo è sul branch `dev`. Non vengono dichiarate in questa policy versioni supportate o tempi di correzione garantiti non verificati: verificare release/tag e ambiente host caso per caso.

## Aree critiche

- **AI agents/tool use**: consentire solo tool e handler di dominio autorizzati; l'AI crea proposte, una persona le conferma, enforcement dello stato sul server.
- **SSRF/networking**: il codice di prospecting non deve trasformare URL non fidati in richieste arbitrarie. Il fetch remoto precedentemente diretto è disabilitato in attesa di un connettore DNS/IP e redirect sicuro [#33](https://github.com/laraxot/module_ai_fila5/issues/33).
- **Consent & privacy**: marketing elettronico soggetto a regole locali e consenso; suppression/do-not-contact, data minimization, retention, audit e separazione canali [#36](https://github.com/laraxot/module_ai_fila5/issues/36).
- **Tenancy/authorization**: thread, proposte, knowledge, prospect e tool limitati per utente/workspace/host policy.
- **State transitions/idempotency**: prevenire doppia conferma, replay di handler e cancellazione post-esecuzione [#42](https://github.com/laraxot/module_ai_fila5/issues/42).
- **Untrusted content**: HTML/CSV/JSON/documenti non sono istruzioni per l'agente; resistere a prompt injection, path traversal, injection in log e output rendering.
- **Supply chain**: valutare dependencies, licenze (Closer MIT, Sellbird AGPL-3.0), GitHub Actions, segreti e permessi minimi.

## Disclosure and tests

Segnalazioni triagiate come P0/P1 sono coordinate in privato quando contengono dettagli sfruttabili; le issue pubbliche descrivono mitigazioni e criteri di test senza pubblicare exploit sensibili. Nessun risultato di PHPStan/Pest, test su host, vulnerability scan o remediation è garantito se non accompagnato da un link a esecuzione e commit verificabili.

Documentazione: [BMAD/ECC operations](../docs/github-ops/00-index.md) · [PRD](../docs/prd.md) · [Prospecting](../docs/prospecting.md).
