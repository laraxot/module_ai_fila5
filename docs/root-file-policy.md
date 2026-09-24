# Root file policy

Updated: 2026-07-08

Root hygiene for this owner follows the project rule: no root .txt files, at most four root .md files, and at most one root .code-workspace named after the module or theme.

Archived root files live in:

- docs/root-txt-files/ (0 files)
- docs/root-md-files/ (2 files)
- docs/root-code-workspace-files/ (1 files)

Current root counts after normalization: 2 markdown files, 0 workspace files.

> **Drift detected (2026-09-17)**: `Modules/AI/` root currently has 2 `.txt` files
> (`AI_pest.txt`, `AI_phpmd.txt`) and 2 `.code-workspace` files
> (`_module_ai.code-workspace`, `_module_ai_fila5.code-workspace` — neither matches
> the name `_ai.code-workspace` already archived under `docs/root-code-workspace-files/`),
> which violates the "no root .txt" / "at most one workspace" rule above. Root `.md`
> count (`ARCHITECTURE.md`, `CHANGELOG.md`, `README.md` = 3) is still within budget.
> This needs a fresh normalization pass (out of scope for a docs-only edit); not
> fixed here per this task's "documentation only, no destructive operations" constraint.
