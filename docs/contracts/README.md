---
title: "Contratti del modulo AI"
type: index
tags: [contracts, actions, ai]
created: 2026-11-18
updated: 2026-11-24
---

# Contratti del modulo AI

Cosa promettono le Action del modulo AI che chiamano fornitori esterni o scrivono dati: la risposta dell'assistente, la
conferma di una proposta dell'AI, la base di conoscenza. Ogni invariante ha un ID `AI-<OPERAZIONE>-INV-<N>`; con un group
Pest si lancia `vendor/bin/pest Modules/AI/tests --group=<ID>`, `—` è debito dichiarato.

Quando leggerli: prima di chiamare, modificare o imitare una di queste Action, in particolare prima di aggiungere un fornitore
o di eseguire una proposta fuori dalla UI. Formato: `Modules/Xot/docs/rules/action-behavioral-contract.md`.

## Contratti

- [RequestAssistantCompletionAction](request-assistant-completion-action.md) — cascata di fornitori LLM con risposta JSON; 5 invarianti, tutte con group.
- [ConfirmAiActionProposalAction](confirm-ai-action-proposal-action.md) — conferma ed esecuzione di una proposta tramite handler; 4 invarianti, tutte con group.
- [SaveKnowledgeEntryAction](save-knowledge-entry-action.md) — voce della base di conoscenza per `collection`; 4 invarianti, 1 con group.
