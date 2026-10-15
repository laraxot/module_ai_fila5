---
updated: 2026-09-26
qmd: "redundancy audit 2026 05 21"
issues: []
discussions: []
title: "AI redundancy audit 2026-05-21"
type: audit
module: AI
tags: [redundancy, duplicate-code, docs]
created: 2026-05-21
related:
  - https://github.com/laraxot/base_fixcity_fila5/issues/89
status: draft
---

# AI redundancy audit 2026-05-21

Static metrics: 302 files scanned, 3 case-only groups, 24 duplicate hash groups, 0 duplicate FQCN.

Findings:
- Case-only docs: `index.md`/`index.md`, `prd.md`/`prd.md`, `roadmap.md`/`roadmap.md`.
- Duplicate docs by dash/underscore variants: `create-an-assistant.md`/`create-an-assistant.md`, `google-gemini.md`/`google-gemini.md`, `fine-tuning.md`/`fine-tuning.md`, `video-tutorial.md`/`video-tutorial.md`.
- MCP docs contain numbered dash/underscore variants such as `11-aggiornamenti.md` and `11_aggiornamenti.md`.

Risk:
- Agents and QMD can retrieve stale duplicates depending on filename convention.
- Case-only names are fragile on WSL and case-insensitive filesystems.

Suggested cleanup order:
1. Keep lowercase-kebab-case docs as canonical.
2. Replace underscore/case variants with links or remove in a docs-only cleanup issue.
3. Update local docs indexes after consolidation.

Evidence commands:
- Per-owner static scan for case-only paths, byte-identical files, and duplicate FQCN.
- GitHub tracker: issue #89.

--- BMAD + Second Brain ---
- Scopo: documentazione tecnica del modulo.
- Utilità: riferimento sviluppatori/audit.
- Funzionalità: struttura, dipendenze, convenzioni (no date .md, git avanti).
