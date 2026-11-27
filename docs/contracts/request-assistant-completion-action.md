---
title: "Contratto — RequestAssistantCompletionAction"
type: contract
action: "Modules\\AI\\Actions\\Assistant\\RequestAssistantCompletionAction"
module: AI
status: active
tags: [contract, action, ai, assistente, provider, servizi-esterni]
created: 2026-11-26
updated: 2026-11-26
related:
  - ../adr/0002-assistant-provider-cascade.md
  - open-ai-compatible-request-action.md
  - make-ai-request-action.md
---

# RequestAssistantCompletionAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/Assistant/RequestAssistantCompletionAction.php`. Chiede la risposta dell'assistente ai fornitori configurati,
in cascata: perché è nell'[ADR](../adr/0002-assistant-provider-cascade.md). Chiamante di produzione:
`Actions/Assistant/AnswerQuestionAction.php`.

Ripristinato il 2026-11-26 dopo la cancellazione nel repository del modulo (commit `2471438`): il docblock e l'ADR lo citavano.

## Input

- `execute(string $systemPrompt, string $prompt)`: prompt di sistema e domanda (con il contesto della base di conoscenza), cioè testo
  scritto da un cittadino.
- Impliciti: `AIConfigData` del tenant (`assistant_providers`, `assistant_models`, `assistant_timeout`, `free_models_only`) e le chiavi
  dei fornitori da `.env`.

## Output

- `string`: il contenuto della risposta del primo fornitore che risponde (il prompt chiede un oggetto JSON).

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-ASSIST-INV-1 | I fornitori si provano nell'ordine di `assistant_providers` (default Groq, poi OpenRouter), ognuno col suo modello e con `response_format: json_object` quando il fornitore lo ammette. | `AI-ASSIST-INV-1` |
| AI-ASSIST-INV-2 | Un fornitore che fallisce (errore HTTP, rete, risposta senza contenuto) passa la mano al successivo; l'errore non risale. | `AI-ASSIST-INV-2` |
| AI-ASSIST-INV-3 | Un fornitore senza chiave si salta senza chiamarlo; se nessuno risponde, la richiesta passa a `MakeAIRequestAction` (OpenAI). | `AI-ASSIST-INV-3` |
| AI-ASSIST-INV-4 | Se nessun messaggio contiene la parola «json», si aggiunge `JSON_INSTRUCTION` al prompt di sistema (senza, Groq risponde 400 a `json_object`). | `AI-ASSIST-INV-4` |
| AI-ASSIST-INV-5 | Con `free_models_only` attivo (default) un fornitore il cui modello non è gratuito si salta. | — (non dimostrato) |

Test: `tests/Unit/Assistant/RequestAssistantCompletionActionTest.php` (4 test, HTTP finto): uno per INV-1…INV-4. Nessun test per INV-5.

## Effetti collaterali

- Una o più `POST` HTTPS verso i fornitori (tramite `OpenAiCompatibleRequestAction`), con la domanda del cittadino e il contesto nel corpo.
- `Log::warning('AI assistant provider failed', provider, error)` per ogni fornitore fallito e
  `Log::warning('AI assistant provider returned no content', provider)` per una risposta vuota.
- Nessuna scrittura sul database, nessuna cache.

## Errori

- Gli errori dei fornitori in cascata (`AiRequestException`) sono assorbiti. Se nessuno risponde e anche `MakeAIRequestAction` fallisce,
  la sua `Exception` («failed after N attempts») risale al chiamante.

## Non fa

- Non salva la risposta né la conversazione (`AppendAiMessageAction`).
- Non decide se rispondere o passare a una persona (`DetectHandoffTriggerAction`).
- Non toglie dal prompt i dati personali prima di inviarlo ai fornitori.
- Non limita la frequenza delle richieste.

## Limiti noti (difetti da correggere, non da imitare)

- **L'ultima spiaggia ignora `free_models_only`.** Se i fornitori in cascata falliscono, `MakeAIRequestAction` (riga 80 dell'Action)
  chiama OpenAI `gpt-4`, a pagamento, anche con `free_models_only` = `true`. La regola del progetto (solo modelli gratuiti) vale
  solo per i fornitori in cascata, e anche INV-5 non ha test.
- Il messaggio d'errore del fornitore (`$exception->getMessage()`) entra nel log e può citare parti del prompt.
- Il testo del cittadino, con il contesto, parte verso fornitori terzi (Groq, OpenRouter, OpenAI) senza oscuramento dei dati personali.

## Riconciliazione documentale (2026-11-27)

Questo file aveva un conflitto Git irrisolto. La base è la variante più recente del contratto, con il riferimento a `app/Actions/Assistant/RequestAssistantCompletionAction.php` **esistente su `dev`**; la sua semantica, i group Pest citati e gli eventuali effetti non sono stati rieseguiti in questa sessione. Il confronto con il codice e i test resta il gate ECC della story owner. [Tracciamento root #15](https://github.com/laraxot/base_ptvx_fila5/issues/15).
