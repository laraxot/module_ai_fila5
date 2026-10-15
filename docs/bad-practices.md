---
<<<<<<< .merge_file_ZyfzYp
id: laraxot-ai-bad-practices-underscore-alias
slug: bad_practices
title: "Alias: bad practices AI"
qmd: "bad practices ai alias"
document_type: alias
source_of_truth: false
canonical: ./bad-practices.md
---

# Alias: bad practices AI

Contenuto canonico: [bad-practices.md](./bad-practices.md). Questo file resta solo per compatibilita dei link; duplicato identico consolidato il 2026-10-15, storia completa nel git history.

--- BMAD + Second Brain ---
- Scopo: documentazione tecnica del modulo.
- Utilità: riferimento sviluppatori/audit.
- Funzionalità: struttura, dipendenze, convenzioni (no date .md, git avanti).
=======
title: "bad practices"
type: note
tags: [documentation]
created: 2026-09-26
updated: 2026-09-26
qmd: "bad practices"
issues: []
discussions: []
status: draft
---

# Bad Practices – AI

## ❌ Usare `file_get_contents` per chiamare API
Usa `Http::` o Guzzle per timeouts/retries.

## ❌ Prompt hard‑coded in view
Sposta i messaggi in `lang/` o in file di configurazione.

## ❌ Non gestire errori API
Implementa fallback e logging di eccezioni.
>>>>>>> .merge_file_V2NBA2
