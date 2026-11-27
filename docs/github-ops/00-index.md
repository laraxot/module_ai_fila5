---
title: "GitHub operations — AI prospecting, BMAD / ECC / Second Brain"
type: operating-model
module: AI
status: active
created: 2026-11-27
updated: 2026-11-27
tags: [github, bmad, ecc, second-brain, prospecting, governance]
qmd: "github issues views projects milestones labels pull requests agents discussions actions wiki security insights settings AI prospecting"
issues:
  - "https://github.com/laraxot/module_ai_fila5/issues/32"
related:
  - ../prospecting.md
  - ../prompts/clients.md
  - ../bmad/prospecting-story.md
---

# GitHub operating model: AI prospecting

**Source of truth:** `dev` + [epic #32](https://github.com/laraxot/module_ai_fila5/issues/32). The AI module remains agnostic: a host project owns ICP, offers, legal policy, customer records, CRM writes and sends. No copying from Sellbird (AGPL-3.0); Closer (MIT) is a conceptual reference. No unapproved outbound marketing.

This page inventories all requested GitHub surfaces and defines their intended use. It does **not** claim a UI-only operation has happened if it has not been verified.

## Snapshot (2026-11-27)

| Surface | Observed state | Action / next gate |
|---|---|---|
| [Issues](https://github.com/laraxot/module_ai_fila5/issues) | Epic #32; focused #33–#42 (numbers not in priority order; #42 is shared approval replay gate) | Link dependencies and record reproducible results |
| [Issue views](https://github.com/laraxot/module_ai_fila5/issues/views) | 0 shared views | Create filters below when authenticated |
| [Projects](https://github.com/laraxot/module_ai_fila5/projects) | 0 linked projects | Create one board, not parallel conflicting boards |
| [Milestones](https://github.com/laraxot/module_ai_fila5/milestones) | 0 milestones | Create 4 phase gates below; no invented dates |
| [Labels](https://github.com/laraxot/module_ai_fila5/labels) | 16 visible labels, including `track-prospecting`, `priority-P0/P1`, `bmad`, `ecc-gate`, `second-brain` | Reuse existing taxonomy before creating new labels |
| [Pull requests](https://github.com/laraxot/module_ai_fila5/pulls) | 5 open Dependabot PRs (#18–#22), each `mergeable=false` at inspection; added `ecc-gate` label | Investigate conflicts and test compatibility; no blind merge |
| [Agents](https://github.com/laraxot/module_ai_fila5/agents) | Three manual-invocation profiles created under `.github/agents/` (BMAD, ECC, Second Brain); account availability unverified | Confirm profile availability in Copilot UI when authenticated |
| [Discussions](https://github.com/laraxot/module_ai_fila5/discussions) | 2 public discussions at inspection (#16, #24) | New RFC only for unresolved architectural decisions, not duplicate issue |
| [Actions](https://github.com/laraxot/module_ai_fila5/actions) | [Prospecting PHP syntax run #5](https://github.com/laraxot/module_ai_fila5/actions/runs/45969852134) **Success** for commit `5f3a72e`; this proves syntax only | Add host-based Pest/PHPStan and security gates (#41) |
| [Wiki](https://github.com/laraxot/module_ai_fila5/wiki) | Public wiki URL redirected to repository root during inspection | Keep canonical QMD second brain in `docs/wiki`; publish a GitHub wiki mirror only if enabled |
| [Security](https://github.com/laraxot/module_ai_fila5/security) | `.github/security.md` exists; admin settings and private alerts not inspected | Review disclosure, code-scanning, secret-scanning, Dependabot, PII and SSRF; #33/#36 |
| [Insights](https://github.com/laraxot/module_ai_fila5/insights) | No metrics asserted | Measure real lead conversion, issue throughput, test pass rate and coverage after instrumentation |
| [Settings](https://github.com/laraxot/module_ai_fila5/settings) | Browser not authenticated; no admin settings changed | Owner review of repo description/topics, branch rules, privileges, features, notifications and security; prevent accidental disruption |

## Priorities and dependencies

1. **M0 — Trust foundation, P0**: #33 SSRF/fetch isolation and failure scoring; #36 consent, channel policy, DNC and retention; #42 server-side approval state/replay security. No production launch or send before both pass their gates.
2. **M1 — Discovery & identity, P1**: #40 schema/tenancy; #39 CSV/provider contracts, licensing, provenance. Import preview and re-import must be safe.
3. **M2 — Insight & operator review, P1**: #37 versioned observed signals/ICP; #38 Filament access-controlled review; #34 inbound + domain-owned CRM adapter.
4. **M3 — Delivery quality, P1/P2**: #41 host runtime tests, static analysis and threat cases; #35 additional Closer capabilities only after pilot evidence.

These are **proposed milestone names**, not existing GitHub milestones. Do not assign dates before estimating effort. A readiness report must cite commit, test command, environment and outstanding risks.

## Issues and issue views

Recommended existing labels:
- `priority-P0`: exploitable security, privacy, data-loss or production blocker.
- `priority-P1`: required MVP scope. Use `enhancement` or `bug` as appropriate.
- `track-prospecting`: all prospecting epic/stories.
- `bmad`: requirements, UX, ADR, architecture, domain boundaries.
- `ecc-gate`: threat tests, PHPStan/Pest/CI, reproducible evidence.
- `second-brain`: source review, knowledge promotion, decisions.
- `documentation`: docs update with QMD frontmatter and valid links.

Proposed shared views:
1. **P0 Safety** — open issues with `priority-P0`.
2. **Prospecting Backlog** — open `track-prospecting`, sort by priority/updated.
3. **ECC Gate** — open `ecc-gate`.
4. **BMAD Planning** — open `bmad`.
5. **Knowledge & Docs** — open `second-brain` OR `documentation`.

Issue template: context → exact file/line/commit evidence → user story → dependencies → acceptance checklist → threat cases → Pest/PHPStan commands → handoff. Avoid duplicates and false “100% coverage” claims.

## Projects

One proposed project **AI Prospecting — BMAD/ECC** with fields:
- `Status`: Inbox / Specified / Ready / In progress / In review / Verified / Blocked / Done.
- `Priority`: P0 / P1 / P2.
- `Phase`: M0 / M1 / M2 / M3.
- `Track`: Product, Data, AI/Signals, UX, Security, DX.
- `Owner`: explicit person, never assigned by guess.
- `Evidence`: URL to passing CI/test report, ADR, or review.

A project status never substitutes for an issue state. “Done” requires merged/on-`dev` code, updated docs and verified checks. Stage changes must be auditable.

## Pull requests and branch policy

The established project preference is **direct work on `dev` unless explicitly changed**. A PR requires a separate source branch; do not create a branch just to make the PR list non-empty. For external contributions and Dependabot PRs: inspect diff/license/dependency compatibility, link an issue, demand relevant checks, review security-sensitive code, then merge only with permission and evidence. Existing #18–#22 are dependency bumps, not proof of validated upgrades.

## Agents and BMAD + ECC process

- **BMAD**: Analyst (evidence + ICP), PM (PRD and measurable criteria), Architect (tenant/contract/data-flow), Dev (small Queueable Actions), Reviewer (security, regression).
- **ECC**: research-first, red/green/refactor when executable, focused lint/Pest/PHPStan, threat modeling, code review, evidence-based status.
- **Second Brain**: link first-party sources, facts vs inference, date/ref/version, ADR if a decision changes boundaries, canonical `docs/wiki` index. Reindex QMD on a host with QMD installed; do not claim indexing occurred without execution.
- Only agents authorized by the user's workflow execute operations; these role names are responsibilities, not evidence that separate GitHub agents have been created.

## Discussions, wiki and Second Brain

Use a Discussion for genuine alternatives (e.g. **source adapters vs host adapters**, **workspace vs tenant isolation**, **whether a legally valid sender is ever in scope**). Move an accepted choice into `docs/adr` and link the epic. Do not duplicate the same question across issues and discussions. Wiki content is a published projection of `docs/wiki`; avoid diverging parallel truths.

## Actions, security, insights and settings

- **Actions**: the new `prospecting-php-syntax.yml` provides syntax checks only. #41 must add Pest, PHPStan and migrations on an actual Laravel 13/PHP 8.4 host or record why a gate is unavailable. Use least-privilege `GITHUB_TOKEN`, timeouts and pinned action versions.
- **Security**: #33 mandates fail-closed HTTP and no internal-network calls; #36 mandates consent provenance and suppression. Redact personal data in CI/logs. Review GHAS/Dependabot settings with repository owner before changing permission-impacting features.
- **Insights**: derive audited counts from source events and project history; distinguish imported, qualified, reviewed, consented, contacted, meetings, conversions and suppressed. Never infer send or conversion from a draft.
- **Settings**: owner-only checklist: visibility, description/topics, default `dev`, branch protection compatibility, Actions permissions, vulnerability alerts, Discussions/Wiki/Projects toggles, private reporting, collaborator access. Review first; changes may affect other Laraxot projects.

## Related evidence

- [Product and implementation study](../prompts/clients.md)
- [Current feature/readiness](../prospecting.md)
- [BMAD story](../bmad/prospecting-story.md)
- [Garante — electronic marketing](https://www.garanteprivacy.it/web/guest/home/docweb/-/docweb-display/docweb/10213792)
- [Garante — public data reuse](https://www.garanteprivacy.it/web/guest/home/docweb/-/docweb-display/docweb/10310279)
- [Closer MIT](https://github.com/Jakeschincariol/closer-skill) · [Sellbird AGPL-3.0](https://github.com/ethanplusai/sellbird)

## Handoff / open limitations

GitHub connector supports Issues, labels on issues, file commits and PR review, but this session has no authenticated browser for creating Projects, Milestones, shared Views, Discussions or GitHub Wiki pages. These remain **specified, not created**. Never claim a settings/security toggle was changed merely because it is documented here.
