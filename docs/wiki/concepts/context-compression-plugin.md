---
name: context-compression-plugin
description: Documentation of the Context Compression plugin (context-mode) for Claude Code, enabling token reduction and efficient multi-step searches.
type: concept
---

# Context Compression Plugin (context-mode)

## Overview
The **Context Compression plugin** (also known as **context‑mode**) is an MCP tool that runs a lightweight sandboxed SQLite FTS5 index. It captures the output of commands, indexes it, and allows fast BM25 searches. This reduces the amount of raw text flowing into Claude’s prompt, typically shrinking a 300 KB output to ~5 KB (≈98 % token saving).

## Installation
The plugin is already listed in the project’s `docs/wiki/concepts/context-mode-mcp.md`. To (re)install or upgrade:
```bash
# Global install (once per environment)
npm install -g context-mode
# Project‑local install (adds to .claude.json)
npx -y context-mode install
```
The command updates `.claude.json` with the required MCP entry and creates the helper script `context-mode`.

## Core Commands
| Command | Description |
|---|---|
| `ctx_batch_execute` | Run multiple shell commands, automatically index their output and make it searchable. |
| `ctx_search` | Query the indexed output using BM25. |
| `ctx_execute` / `ctx_execute_file` | Run code (JS, Python, etc.) and index only the printed summary. |
| `ctx_stats` | Show token savings and index size. |
| `ctx_doctor` | Diagnose the plugin installation. |
| `ctx_upgrade` | Upgrade the plugin to the latest version. |
| `ctx_purge` | **Irreversible** – clears the entire knowledge base. |

## Recommended Workflow
1. **Gather** – Use `ctx_batch_execute` for any command that may produce >20 lines (e.g., `git log`, `grep -R`).
2. **Search** – Immediately follow with `ctx_search` for specific questions. This avoids pulling raw output into Claude’s context.
3. **Process** – If you need calculations, use `ctx_execute` (e.g., JSON parsing, statistics).
4. **Persist** – Updated docs should be added to the local LLM Wiki (`docs/wiki/`) and linked from the module’s index.

## Integration with BMAD
BMAD agents rely on this plugin for heavy research steps. The **bmad-context-compression-operations** runbook (see `bashscripts/docs/wiki/concepts/bmad-context-compression-operations.md`) demonstrates typical patterns.

## Troubleshooting
- Run `ctx_doctor` to verify the SQLite database and binaries are functional.
- If `ctx_stats` shows 0 indexed items, ensure you invoked `ctx_batch_execute` with at least one command.
- For permission errors, confirm the current user can write to `.claude/context-mode/`.

## Logging
All operations are recorded in `docs/wiki/log.md` with timestamps and command signatures. Example entry:
```
2026-04-22 14:35:12 – ctx_batch_execute – git log --oneline – indexed 12 entries – 3 KB
```

## References
- [Context‑mode MCP documentation](/docs/wiki/concepts/context-mode-mcp.md)
- [BMAD Method – Context Compression](/docs/wiki/concepts/bmad-method-overview.md)
- Official repo (if public): https://github.com/anthropic/context-mode
