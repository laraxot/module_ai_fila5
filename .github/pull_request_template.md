# Scopo / BMAD story

- Issue collegata: #
- Branch base: `dev` (preferenza corrente del progetto)
- Persona / outcome e confini tra modulo AI e dominio host:

## Modifiche

- Contratti / schemi / migrazioni / API:
- Perché questa soluzione; alternative considerate:
- Fonti/licenze esterne utilizzate (Sellbird AGPL solo come ispirazione, codice non copiato):

## Safety e privacy (obbligatorio)

- [ ] Nessun contatto marketing o invio automatico introdotto senza policy/consenso documentati
- [ ] Provenance, DNC/suppression e workspace/tenant authorization verificati ove pertinenti
- [ ] Fetch esterni, SSRF/DNS/redirect, input HTML/CSV, prompt injection e costi riesaminati
- [ ] Nessun dato personale reale, chiave o segreto nei test, commit e log
- [ ] Side effects con human approval e idempotenza, no replay/race

## ECC evidence

- [ ] `php -l` (file modificati)
- [ ] `vendor/bin/pint --test` o pass equivalente
- [ ] `vendor/bin/phpstan analyse Modules/AI` su progetto host
- [ ] `vendor/bin/pest Modules/AI/tests` su progetto host
- [ ] Migrazioni e rollback su database test disponibili
- [ ] Test browser su UI Filament, se modificata
- [ ] Controllo dipendenze/licenze/Actions e review security
- [ ] Docs BMAD/ADR/wiki QMD aggiornati; link validi

**Comandi, commit, host, risultato e limitazioni** (non dichiarare un gate superato se non eseguito):

```text
TODO: paste safe/redacted evidence
```

## Impatto su GitHub / Second Brain

- Epics/stories/labels/milestones aggiornate:
- Fonti/decisioni promosse o invalidate:
- Follow-up / rischi non risolti:

> Non fare merge automatico senza verificare compatibilità e chiedere la decisione appropriata se la modifica tocca sicurezza, privacy, permessi o invii.
