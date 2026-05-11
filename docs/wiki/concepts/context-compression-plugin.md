---
name: context-compression-plugin
<<<<<<< HEAD
description: Installazione e configurazione del plugin context-compression con context-mode MCP.
=======
description: Documentation of the Context Compression plugin (context-mode) for Claude Code, enabling token reduction and efficient multi-step searches.
>>>>>>> c3b9b5924 (.)
type: concept
---

# Context Compression Plugin (context-mode)

<<<<<<< HEAD
## Scopo

Quando una richiesta supera la context window del modello, servono due livelli:

1. plugin OpenRouter `context-compression` nella request API;
2. retrieval locale tramite `context-mode` MCP per non inviare output grezzi molto lunghi.

## Installazione

```bash
npm install -g context-mode@latest
```

## Configurazione

### Provider OpenRouter

Nel file `.agents/config.json`:

```json
{
  "provider": {
    "openrouter": {
      "options": {
        "plugins": [{ "id": "context-compression" }]
      }
    }
  }
}
```

### MCP server context-mode

Sempre in `.agents/config.json`:

```json
{
  "mcpServers": {
    "context-mode": {
      "command": "npx",
      "args": ["-y", "context-mode"]
    }
  }
}
```

## Verifica operativa

- `command -v context-mode` deve restituire il path del binario.
- `npm list -g context-mode --depth=0` deve mostrare la versione installata.
- `.agents/config.json` deve essere JSON valido.

## Note pratiche

- `context-mode --help` avvia il server MCP su stdio (comportamento normale).
- Il plugin OpenRouter non sostituisce la disciplina docs/wiki: evita overflow, non crea memoria persistente.

## Collegamenti

- [openrouter-context-compression](./openrouter-context-compression.md)
- [index wiki modulo AI](../index.md)
- [log wiki modulo AI](../log.md)
- [context-mode-mcp root](../../../../../../docs/wiki/concepts/context-mode-mcp.md)
- [context-compression-plugin-openrouter root](../../../../../../docs/wiki/concepts/context-compression-plugin-openrouter.md)
=======
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
>>>>>>> c3b9b5924 (.)
