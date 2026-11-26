# AI behavioral contract index

This is the machine-readable inventory of behavioral contracts owned by the **AI** module.

A contract describes an existing public operation or boundary. It names the owner, input, output, stable invariants, side effects and their order, errors, and non-goals. Every invariant gets a stable ID and a Pest group when a test exists; missing coverage is recorded as debt instead of being implied away.

## Contracts
- [append-ai-message-action](append-ai-message-action.md)
- [cancel-ai-action-proposal-action](cancel-ai-action-proposal-action.md)
- [classify-ticket-action](classify-ticket-action.md)
- [confirm-ai-action-proposal-action](confirm-ai-action-proposal-action.md)
- [create-ai-action-proposal-action](create-ai-action-proposal-action.md)
- [create-ai-thread-action](create-ai-thread-action.md)
- [generate-predictions-action](generate-predictions-action.md)
- [make-ai-request-action](make-ai-request-action.md)
- [open-ai-compatible-request-action](open-ai-compatible-request-action.md)
- [request-assistant-completion-action](request-assistant-completion-action.md)
- [save-knowledge-entry-action](save-knowledge-entry-action.md)
- [suggest-solutions-action](suggest-solutions-action.md)

## Authoring

Use [Xot contract authoring standard](../../../Xot/docs/contracts/contract-authoring-standard.md) and the shared template. Legacy behavior is documented as-is and is not a model for new code.
