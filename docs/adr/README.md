---
title: "ADR del modulo AI"
type: index
tags: [adr, decisioni, ai]
created: 2026-11-18
updated: 2026-11-24
---

# ADR del modulo AI

Il **perché** delle forme di codice del modulo AI: come l'assistente sceglie il fornitore senza costi imprevisti, perché l'AI
propone invece di eseguire, perché gli id utente sono stringhe.

Quando leggerli: prima di aggiungere un fornitore o un modello, di far "agire" un agente sui dati, o di toccare le migrazioni e
le Action che salvano un id utente. Formato e regole: `Modules/Xot/docs/rules/action-behavioral-contract.md`.

## Decisioni

- [L'assistente chiede la risposta a più fornitori in cascata](assistant-provider-cascade.md) — Groq → OpenRouter → OpenAI, solo modelli gratuiti nella cascata.
- [L'AI propone, una persona conferma, un handler del dominio esegue](ai-proposes-human-confirms.md) — `AiActionProposal` + `AiActionHandlerRegistry`, il modulo non conosce i domini.
- [Gli id utente nelle tabelle AI sono stringhe](user-ids-are-strings.md) — `string(36)` e parametri `int|string`, mai `(int) Auth::id()`.
