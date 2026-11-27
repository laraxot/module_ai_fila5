# GitHub ops — module_ai_fila5

Repo: https://github.com/laraxot/module_ai_fila5 (public, default `dev`).
Indice canonico nel repo: `docs/github-ops/00-index.md` (aggiornato 2026-11-27).

## Superfici

| Superficie | Stato (2026-11-27) |
|---|---|
| Issues | ~35 aperte (#9–#43+). Epic prospecting **#32** con figli **#33–#43**; doc/chore #23–#31; legacy/test/coverage #8–#17 |
| Labels | `track-prospecting`, `priority-P0`, `priority-P1`, `ecc-gate`, `bmad`, `second-brain` + standard GitHub (bug, enhancement, documentation, …) |
| Milestone | **#1 P0 - sicurezza e privacy** · **#2 M1 - persistenza e import** · **#3 M2 - segnali, UX e qualita** · **#4 M3 - outreach, pipeline e inbound** |
| PRs | Dependabot bumps + PR normali su `dev` |
| Discussions | #16 (ponytail-audit sentiment), #24 (README QMD), #44 (studio closer-skill + sellbird) |
| Actions | workflow: `lint`, `PHPStan`, `Prospecting PHP syntax`, `Git Version 01`, `Dependabot Updates` |
| Projects | da configurare in UI se serve |
| Security | repo pubblico → Dependabot attivo; issue SECURITY #33, #36, #42 |
| Wiki | questa wiki (repo `.wiki.git`); SSoT resta `docs/` |

## Mappa issue per tema

- **Prospecting epic**: #32 → figli #33 (SSRF/verify P0), #34 (inbound), #35
  (skill Closer), #36 (privacy P0), #37 (evidence/ICP), #38 (UX Filament),
  #39 (import), #40 (persistenza/tenant), #41 (quality/CI), #42 (action
  proposal security P0), #43 (outreach lifecycle)
- **Docs/chore**: #23, #28, #29, #30, #31
- **Modulo/agente**: #8, #9, #12, #13, #14, #15, #17, #25, #26, #27

Nota: un secondo agente può aprire issue/discussion aggiuntive — fare sempre
`gh issue list -R laraxot/module_ai_fila5 --state open` per lo stato reale.

## Comandi utili

```bash
gh issue list -R laraxot/module_ai_fila5 --state open --json number,title,labels
gh api repos/laraxot/module_ai_fila5/milestones
gh api repos/laraxot/module_ai_fila5/discussions
git -C laravel/Modules/AI status --short   # repo indipendente!
```

## Second brain

- `docs/index.md` — indice documentazione
- `docs/github-ops/00-index.md` — tabella superfici (questo file è il mirror GitHub)
- Label `second-brain` marca le issue di governance knowledge
