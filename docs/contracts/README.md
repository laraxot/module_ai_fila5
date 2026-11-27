---
title: "Contratti del modulo AI"
type: index
tags: [contracts, actions, ai]
created: 2026-11-18
updated: 2026-11-26
---

# Contratti del modulo AI

Cosa promettono le Action del modulo AI che chiamano fornitori esterni o scrivono dati: la risposta dell'assistente, la
conferma di una proposta dell'AI, la base di conoscenza. Ogni invariante ha un ID `AI-<OPERAZIONE>-INV-<N>`; con un group
Pest si lancia `vendor/bin/pest Modules/AI/tests --group=<ID>`, `—` è debito dichiarato.

## Contratto di comportamento di un'Action

Un file per Action, nome in kebab-case della classe (`create-ticket-action.md`), frontmatter `type: contract` e
`action: <FQCN>`, e queste sei sezioni nell'ordine:

```markdown
## Input
## Output
## Invarianti
## Effetti collaterali
## Errori
## Non fa
```

Ogni invariante ha un ID `AI-<OPERAZIONE>-INV-<N>` e, se esiste un test, un group Pest con lo stesso nome
(`cd laravel && vendor/bin/pest Modules/AI/tests --group=<ID>`); `— (non dimostrato)` e' debito dichiarato. Il docblock dell'Action
contiene `@see docs/contracts/<file>.md` e `@contract docs/contracts/<file>.md` (lo controlla
`Modules/Xot/tests/Unit/Docs/ActionContractsAreCompleteTest.php`). Chi cambia il comportamento aggiorna il contratto nello stesso commit.

`INDEX.md` e `index.md` sono i registri operativi previsti da `Modules/Xot/docs/adr/0012-behavioral-contract-governance.md`; questo README
resta l'indice richiesto dai test di pacchetto.

## Contratti

Le Action che scrivono dati o chiamano fornitori esterni, in ordine di rischio.

- [MakeAIRequestAction](make-ai-request-action.md) — la richiesta `chat/completions` a OpenAI (`gpt-4`) con tentativi; 5 invarianti. Scrive nei log testo del cittadino e corpo della risposta.
- [RequestAssistantCompletionAction](request-assistant-completion-action.md) — la cascata Groq → OpenRouter → OpenAI dell'assistente; 5 invarianti. L'ultima spiaggia ignora `free_models_only`.
- [OpenAiCompatibleRequestAction](open-ai-compatible-request-action.md) — il `POST` verso un fornitore compatibile OpenAI con la sua chiave; 4 invarianti, prove solo indirette.
- [ClassifyTicketAction](classify-ticket-action.md) — categoria e tag di una segnalazione via LLM, con cache di un'ora; 3 invarianti. Manda il testo del cittadino a OpenAI.
- [SuggestSolutionsAction](suggest-solutions-action.md) — soluzioni per una segnalazione via LLM, con cache di 30 minuti; 3 invarianti.
- [ConfirmAiActionProposalAction](confirm-ai-action-proposal-action.md) — conferma ed esegue una proposta tramite il registry di handler; 5 invarianti. Non controlla lo stato di partenza.
- [CreateAiActionProposalAction](create-ai-action-proposal-action.md) — registra una proposta `pending` in attesa di conferma umana; 3 invarianti.
- [CancelAiActionProposalAction](cancel-ai-action-proposal-action.md) — porta una proposta a `cancelled` senza eseguirla; 2 invarianti. Non registra chi annulla.
- [AppendAiMessageAction](append-ai-message-action.md) — aggiunge un messaggio a una conversazione e ne aggiorna l'ultima attivita'; 2 invarianti. Nessuna scadenza di conservazione.
- [CreateAiThreadAction](create-ai-thread-action.md) — apre una conversazione (anche anonima); 3 invarianti.
- [SaveKnowledgeEntryAction](save-knowledge-entry-action.md) — crea o aggiorna una voce della base di conoscenza di una collection; 4 invarianti.
- [GeneratePredictionsAction](generate-predictions-action.md) — genera una previsione via OpenAI; legacy di un altro progetto, senza chiamanti; 4 invarianti.

Nota (non è un contratto di Action, ma vive in questa cartella): [website-signals.md](website-signals.md) — descrive i
point di ingresso per i segnali del sito web (`DetectWebsiteSignalsAction` fail-closed finché non esiste un adapter
SSRF-safe, `analyzeHtml`/`analyzeHtmlResult` per HTML fornito dall'host). Non ha il formato a sei sezioni e non è una
promessa di un'Action del dominio.

## Stato di copertura (2026-11-26)

| Misura | Valore |
|---|---|
| Contratti completi (sei sezioni, `action` esistente, `@contract` nel docblock) | 12 |
| Invarianti dichiarate | 43 |
| Con group Pest e test che le esercita | 17 |
| Non dimostrate | 26 |
| Action che scrivono o chiamano servizi esterni senza contratto (audit) | 2, entrambe duplicati morti (vedi sotto) |

I 17 group sono stati assegnati leggendo i test, non eseguendoli (Pest non gira in questa sessione): il test e' la prova solo se e' verde.
Il file `tests/Unit/Actions/GeneratePredictionsActionTest.pest.php` ha un suffisso che Pest probabilmente non raccoglie per cartella.

## Backlog contract

- `Support\MakeAIRequestAction` e `Support\RequestChatCompletionAction`: duplicati morti di `MakeAIRequestAction` (stesso `POST` a OpenAI), senza
  chiamanti nel repository. Da cancellare o da fondere: decisione umana, e finche' esistono sono un secondo percorso verso OpenAI.
- `Assistant\AnswerQuestionAction`: orchestra l'assistente (guardrail, ricerca, modello, messaggi, proposta `assistant_handoff`); scrive
  messaggi e proposte e chiama `RequestAssistantCompletionAction`. E' il prossimo contratto da scrivere, perche' e' il flusso intero.
- `Vision\ExtractStructuredDataFromImagesAction` e `Vision\EditImageAction`: mandano immagini a OpenRouter o Groq; privacy delle immagini da dichiarare.
- `Agent\StreamAgentRunAction`: endpoint AG-UI in streaming (SSE) che fa girare un agente; superficie esposta, da contrattualizzare con l'autorizzazione dei thread.
- `Ollama\ChatOllamaAction`, `Ollama\GenerateOllamaAction`, `Ds4\ChatDs4Action`, `Ds4\GenerateDs4Action`, `CompletionAction`: chiamate a server locali; rischio piu' basso (nessun terzo), da fare dopo.
- `Predict\GeneratePredictionDraftsAction`: bozze di previsione, legacy dello stesso progetto di `GeneratePredictionsAction`.

## Backlog test

Invarianti senza prova, in ordine di rischio:

- `AI-CONFIRM-INV-5` e il controllo dello stato di partenza (oggi assente): un test che riconfermi una proposta `executed` e chiuda il punto sul codice.
- `AI-ASSIST-INV-5`: un fornitore con modello a pagamento e `free_models_only` attivo non viene chiamato (l'ADR 0002 lo dava per provato).
- `AI-REQUEST-INV-5`, `AI-CLASSIFY-INV-1`/`3`, `AI-SUGGEST-INV-1`/`3`: cache, risposta vuota e ripiego su `DEFAULTS`.
- `AI-HTTP-INV-1`…`4`: test diretti su `OpenAiCompatibleRequestAction` (oggi solo prove indirette dalla visione).
- `AI-PROPOSE-*`, `AI-CANCEL-*`, `AI-THREAD-*`, `AI-MESSAGE-*`, `AI-KNOWLEDGE-INV-2`…`4`: servono tabelle `ai_*` e un database di prova (le migrazioni stanno su un'altra connessione).
- `AI-PREDICT-INV-3`/`4`: prima va chiarito se `execute()` arriva in fondo (ricorsione `PredictionData::from()` segnalata nel test).

Difetti trovati leggendo il codice (non corretti: sono codice, non docs): dati personali nei log di `MakeAIRequestAction`, `gpt-4` a pagamento fisso
usato da Classify e Suggest e come ultima spiaggia dell'assistente, nessun controllo di stato in Confirm e Cancel, `GeneratePredictionsAction` senza chiamanti
e con possibile ricorsione. Dettaglio nei «Limiti noti» dei singoli contratti.

## Harness minimo: ownership, evidenza e recupero (2026-11-27)

I contratti di **module_ai_fila5** restano accanto alle Action, interfacce o superfici di questo package; root e Xot definiscono lo standard, non copiano le promesse. Un contratto comportamentale esplicita **Input, Output, Invarianti, Effetti collaterali, Errori, Non fa**; ogni ID di invariante punta a un group Pest effettivo oppure segnala `non dimostrata`. Se esiste la classe, il docblock deve rimandare al file con `@contract` e `@see`; per temi senza classi PHP non inventare Action/contratti.

- [Standard Xot](https://github.com/laraxot/module_xot_fila5/blob/dev/docs/contracts/contract-authoring-standard.md) · [ownership root](https://github.com/laraxot/base_ptvx_fila5/blob/dev/docs/adr/0001-harness-minimo-e-ownership.md) · [runbook ECC/Second Brain](https://github.com/laraxot/base_ptvx_fila5/blob/dev/docs/harness/README.md).
- **BMAD**: prima di cambiare una promessa, fissa nella story owner scope, esclusioni, evidenza e approvazioni richieste.
- **ECC**: i marker di merge rendono invalido l'indice; citare una suite non significa averla eseguita. Distingui `pass`, `fail`, `non eseguito` e registra il debito in [rollout #15](https://github.com/laraxot/base_ptvx_fila5/issues/15).
