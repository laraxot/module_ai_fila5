---
name: context-compression-plugin
description: Installazione e configurazione del plugin context-compression con context-mode MCP.
type: concept
---

# Context Compression Plugin (context-mode)

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
