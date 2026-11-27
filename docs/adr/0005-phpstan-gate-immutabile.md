---
title: "ADR — Gate PHPStan immutabile e obbligatorio"
type: decision
status: accepted
module: AI
decision-makers: AI module owner
created: 2026-11-27
updated: 2026-11-27
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
tags: [adr, phpstan, quality, ecc, bmad]
qmd: "AI PHPStan zero errors immutable gate baseline ignore configuration"
discussions:
  - "https://github.com/laraxot/module_ai_fila5/discussions/24"
---

# ADR: gate PHPStan immutabile e obbligatorio

## Contesto

Il modulo AI deve mantenere il livello PHPStan configurato senza accumulare debito statico o nascondere segnalazioni. Un report verde
ottenuto alterando la configurazione non dimostra la qualità del codice. Domanda: quale condizione consente di chiudere un lavoro
che coinvolge il modulo?

## Driver della decisione

- Il livello PHPStan configurato è il contratto tecnico del modulo.
- Baseline, ignore e modifiche opportunistiche alla configurazione nascondono difetti e rendono il gate non confrontabile.
- La verifica deve essere ripetibile prima della chiusura di ogni lavoro rilevante.

## Alternative scartate

- Aggiungere una baseline per rendere il report verde: congela il debito invece di correggerlo.
- Aggiungere `@phpstan-ignore` o escludere percorsi: sopprime il segnale e trasferisce il rischio ai lavori successivi.
- Modificare `phpstan.neon` per abbassare o aggirare il gate: altera il contratto senza una decisione architetturale separata.

## Decisione

- Al livello PHPStan già configurato, il risultato deve essere **zero errori**.
- L'agente non modifica baseline, `@phpstan-ignore`, altri ignore o `phpstan.neon` per ottenere il risultato.
- Prima di chiudere il lavoro è obbligatorio eseguire da `laravel/`:

  ```bash
  ./vendor/bin/phpstan analyse Modules/AI --memory-limit=-1
  ```

- Se il comando fallisce, il lavoro resta aperto e le segnalazioni vengono corrette alla radice. Un'eccezione richiede un nuovo ADR
  esplicito; non può essere introdotta tacitamente nella configurazione.

## Conseguenze

- Pro: report azionabili, qualità verificabile e nessun debito statico nascosto.
- Contro: la chiusura richiede tempo per correggere anche gli errori preesistenti incontrati nello scope.
- Operativamente: il risultato del gate va riportato nella consegna insieme al comando eseguito e al conteggio degli errori.

## Conferma

- Gate manuale e CI: il comando PHPStan sopra deve terminare con codice 0 e zero errori.
- Review: ogni diff che modifica baseline, ignore o `phpstan.neon` è rifiutato se motivato solo dal superamento del gate.
- Il lavoro non è completo finché il gate obbligatorio non è verde.
