---
title: "ADR del modulo AI"
type: index
tags: [adr, decisioni, ai]
created: 2026-11-18
updated: 2026-11-26
qmd: "AI ADR governance PHPStan gate"
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
discussions:
  - "https://github.com/laraxot/module_ai_fila5/discussions/24"
---

# ADR del modulo AI

Il **perche'** delle scelte di questo pacchetto: una decisione per file, con le alternative scartate.
Il nome del file e' `NNNN-argomento.md` (numero di decisione, niente date: la data sta nella frontmatter).

## Formato

```markdown
---
title: "ADR — <decisione in una riga>"
type: decision
status: accepted | proposed | rejected | deprecated | superseded   # se superseded: superseded_by
created: AAAA-MM-GG
updated: AAAA-MM-GG
module: AI
decision-makers: <chi decide>
issues: []
---

# ADR: <decisione>

## Contesto
Fatti e vincoli che hanno reso necessaria la scelta (2-3 frasi) e la domanda a cui risponde.

## Driver della decisione
Le forze che pesano: norme, misure, vincoli tecnici, incidenti.

## Alternative scartate
Ogni alternativa seria, con il motivo per cui non e' stata scelta.

## Decisione
Cosa si fa, in concreto, e dove sta nel codice.

## Conseguenze
Pro, contro, debito che resta, cosa misurare. Chi cambia la decisione scrive un nuovo ADR e segna questo `superseded`.

## Conferma
Come si controlla che il codice rispetti la decisione: un test (percorso), un comando eseguibile da `laravel/` o una regola
di review. Se oggi non esiste nulla di automatico: «Nessuna conferma automatica oggi; proposta: ...».
```

Se il codice di oggi contraddice la decisione, si aggiunge `## Stato attuale` con cio' che fa davvero (file e riga). Il test
`Modules/Xot/tests/Unit/Docs/PackagesHaveContractsAndAdrTest.php` impone le intestazioni in italiano.

## Gate PHPStan

Per il modulo AI vale anche [0005 — Gate PHPStan immutabile e obbligatorio](0005-phpstan-gate-immutabile.md): al livello configurato
il report deve avere zero errori prima di chiudere il lavoro. L'agente non modifica baseline, ignore o `phpstan.neon` per ottenere un
report verde; corregge invece le cause nel codice oppure lascia il lavoro aperto.

## Decisioni

- [0001 — L'AI propone, una persona conferma, un handler del dominio esegue](0001-ai-proposes-human-confirms.md) — `accepted`: l'AI crea una `AiActionProposal`, la esegue solo `ConfirmAiActionProposalAction` tramite il registry di handler del dominio.
- [0002 — L'assistente chiede la risposta a piu' fornitori in cascata, solo modelli gratuiti](0002-assistant-provider-cascade.md) — `accepted`: Groq, poi OpenRouter, poi OpenAI; l'ultimo tentativo ignora `free_models_only` (`## Stato attuale`).
- [0003 — Gli id utente nelle tabelle AI sono stringhe da 36 caratteri](0003-user-ids-are-strings.md) — `accepted`: cinque colonne `string(36)` al posto di interi, per non troncare gli UUID a 0.
- [0005 — Gate PHPStan immutabile e obbligatorio](0005-phpstan-gate-immutabile.md) — `accepted`: zero errori al livello configurato, nessuna soppressione o modifica della configurazione per eludere il gate.

## Stato di copertura (2026-11-26)

- 3 decisioni, tutte con le sei sezioni in italiano, `decision-makers` e `Conferma`; 0001 e 0002 hanno anche `## Stato attuale`.
- Conferma con un test indicato: 0001 (group `AI-CONFIRM-INV-3`/`INV-4`, registry), 0002 (`RequestAssistantCompletionActionTest`, 4 test, solo
  per l'ordine e i passaggi di mano), 0003 (`UserIdColumnsAcceptUuidTest`, schema). Non rieseguiti in questa revisione.
- Senza conferma automatica: lo stato di partenza `pending` (0001), `free_models_only` per modelli a pagamento (0002), `(int)` sugli
  id utente nelle Action (0003).
- Il codice contraddice due volte la lettera degli ADR: nessun controllo di stato nelle Action di conferma/annullamento (0001) e
  l'ultimo tentativo OpenAI a pagamento (0002). Decisioni umane aperte, vedi gli ADR.
- Il documento `docs/deferred-operator-assistant-agent.md`, citato da 0001, e' stato cancellato dal commit `2471438` del repository del modulo.

## Harness minimo: ownership, evidenza e recupero (2026-11-27)

Questo indice appartiene a **module_ai_fila5**: ogni decisione su codice, dominio o tema va nell'ADR del package owner; il root registra soltanto i vincoli di composizione. Un ADR richiede una scelta reale con alternative e conseguenze, più una **Conferma** ripetibile (oppure la dichiarazione esplicita che manca). Non creare ADR fittizi per riempire un indice.

- [Confine LaravelDay/harness Xot](https://github.com/laraxot/module_xot_fila5/blob/dev/docs/adr/0042-laravel-day-harness-adoption-boundary.md); [ownership root](https://github.com/laraxot/base_ptvx_fila5/blob/dev/docs/adr/0001-harness-minimo-e-ownership.md).
- **BMAD**: la story owner-local conserva `scope`, `must_not_change`, `evidence`, `approval_required` nei cambi rischiosi.
- **ECC**: nessun marker di merge; distinguere la prova eseguita dal semplice riferimento a un test; se la conferma non è disponibile segnare `non eseguita`.
- **Second Brain**: QMD/Graphify e `.harness/memory/{facts,lessons}.md` sono puntatori a evidenza datata, non copie degli ADR. [Runbook](https://github.com/laraxot/base_ptvx_fila5/blob/dev/docs/harness/README.md) · [Rollout #15](https://github.com/laraxot/base_ptvx_fila5/issues/15).
