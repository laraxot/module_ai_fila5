---
title: "ADR — PHPStan a zero errori: mai saltare le segnalazioni"
type: decision
status: accepted
module: AI
decision-makers: AI module owner
created: 2026-11-27
updated: 2026-11-27
tags: [adr, phpstan, quality, ecc]
---

# ADR: PHPStan a zero errori, sempre

## Contesto

- Il modulo AI è a livello PHPStan L10 con regole rigide (niente `@phpstan-ignore`, baseline,
  `assert()`, `@var` inline o cast aggiunti solo per zittire l'analisi: vedi le istruzioni in testa
  a ogni report).
- Durante lo sviluppo possono restare errori PHPStan aperti su file nuovi o toccati.
- Domanda: cosa succede quando PHPStan segnala errori nel modulo?

## Driver della decisione

- Un errore PHPStan è un difetto reale (tipo mancante, `mixed` non ristretto, funzione non sicura)
  o una vera imprecisione: si corregge alla radice, mai si archivia.
- Il costo di un fix immediato è basso; quello di un debito accumulato in un modulo condiviso no.
- Coerenza con l'ECC gate: `php -l` + PHPStan + PHPMD + PHPInsights + Pest dopo ogni modifica PHP.

## Alternative scartate

- Baseline / `@phpstan-ignore`: mascherano il debito e rendono i report futuri illeggibili.
- Fix "quando si passa di lì": è esattamente il comportamento che ha lasciato 52 errori aperti.
- Escludere `tests/` dall'analisi: i test sono codice del modulo e vanno tenuti allo stesso livello.

## Decisione

- **Nessun errore PHPStan viene mai saltato o ignorato** in `Modules/AI` (e per estensione in ogni
  modulo). Ogni segnalazione si corregge con narrowing reale dei tipi, PHPDoc array shape,
  varianti `Safe\*` di `thecodingmachine/safe`, generics corretti sui Builder.
- Prima di chiudere un lavoro che tocca PHP nel modulo: `./vendor/bin/phpstan analyse Modules/AI
  --memory-limit=-1` deve tornare **0 errori**; in caso contrario il lavoro non è finito.
- Le nuove classi/nascenti file PHP devono nascere conformi (type declarations esplicite, PHPDoc
  dove servono, niente `mixed` non governato).

## Conseguenze

- Pro: report sempre azionabili, niente debito nascosto, sicurezza reale (Safe functions).
- Contro: ogni modifica richiede un giro PHPStan completo sul modulo.

## Conferma

- Report attuale: 0 errori su `Modules/AI` (verificato il 2026-11-27 dopo la pulizia del result
  cache corrotto e la correzione dei 52 errori pre-esistenti).
- CI: il workflow PHPStan esistente è il gate; una PR con errori non deve mergiare.
- Regola di review: qualunque `@phpstan-ignore`, baseline o suppressione nuova in `Modules/AI` è
  motivo di reject.
