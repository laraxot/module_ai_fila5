# Convenzioni di sviluppo — module_ai_fila5

Fonte canonica: `AGENTS.md` root del progetto ospite, `laravel/AGENTS.md`,
`laravel/CLAUDE.md` (Laravel Boost guidelines), `docs/wiki/`. Questo modulo è un
**repo Git indipendente** (`laraxot/module_ai_fila5`, branch `dev`): non è un
submodule — verificare sempre `git -C Modules/AI status` e
`rev-list --left-right --count` contro il remote.

## PHP / Laravel

- `declare(strict_types=1);` ovunque; type hints e return types espliciti;
  constructor property promotion; niente `__construct()` vuoti.
- Azioni in `app/Actions/` (pattern Actions, non Service class); contract in
  `app/Contracts/` con naming dedicato (`docs/contracts-naming.md`).
- **Mai estendere Filament diretto** — sempre XotBase/LangBase
  (`docs/concepts/xotbase-never-extend-filament.md`). Le Resources in
  `app/Filament/Resources/` ereditano dalle basi Laraxot.
- Modelli estendono `XotBaseModel`; casts in metodo `casts()`, non property.
- Migrazioni sempre additive; mai `RefreshDatabase` nei test — `.env.testing`
  usa SQLite.

## Testing

- Pest con `it()` — niente classi PHPUnit.
- Dopo **ogni** modifica PHP: `php -l` + PHPStan (`./vendor/bin/phpstan analyse
  Modules --memory-limit=-1`, livello massimo) + PHPMD (`laravel/tools/phpmd.sh`)
  + PHPInsights (`laravel/tools/phpinsights.sh`) + Pest filtrato
  (`php artisan test --filter="..."`).
- **PHPStan a zero errori, sempre** (ADR `docs/adr/0004-phpstan-zero-errori.md`):
  nessuna segnalazione si salta o si ignora — niente `@phpstan-ignore`, baseline,
  `assert()`, `@var` inline o cast di comodo. Fix = narrowing reale dei tipi,
  PHPDoc array shape, varianti `Safe\*`. Un lavoro PHP non è finito se
  `phpstan analyse Modules/AI` non torna 0 errori.
- Ogni modifica deve essere coperta da test nuovo o aggiornato.

## Git / processo

- Commit **solo su richiesta esplicita** dell'utente (eccezione: regole CLAUDE del
  progetto ospite per commit su `dev` — verificare il contesto).
- Commit di Claude citano la GitHub issue (`#N`).
- Stesso task multi-agente → `docs/chat/INDEX.md` + issue GitHub.
- Docs in `docs/` con **frontmatter YAML obbligatorio**: `title`, `type`,
  `tags`, `created`, `updated`, `qmd`, `issues`, `discussions`.

## Dominio prospecting (regole ECC)

- Solo dati da fonti pubbliche; bloccare host privati/localhost (SSRF).
- Mai email inventate; soppressione always-on (hashed, workspace-scoped).
- Draft-only: nessun canale di invio automatico; `lawful_basis` obbligatoria
  sulle bozze (`docs/prospecting.md`).

## Struttura docs

- `docs/` = SSoT operativo (max ~100 file .md, vedi issue #28).
- `docs/wiki/` = wiki LLM modulo (Karpathy pattern: `concepts/`, `entities/`,
  `sources/`, `comparisons/`, `decisions/`, `troubleshooting/`, `_archive/`,
  `_templates/`; `raw/` immutabile).
- `docs/github-ops/` = mappa superfici GitHub → vedi [[GitHub-ops]].
- `docs/bmad/` = artefatti BMAD (story, architettura).
