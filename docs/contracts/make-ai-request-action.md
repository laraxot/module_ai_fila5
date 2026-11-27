---
title: "Contratto — MakeAIRequestAction"
type: contract
action: "Modules\\AI\\Actions\\MakeAIRequestAction"
module: AI
status: active
tags: [contract, action, ai, openai, retry, servizi-esterni]
created: 2026-11-26
updated: 2026-11-26
related:
  - classify-ticket-action.md
  - suggest-solutions-action.md
  - request-assistant-completion-action.md
---

# MakeAIRequestAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
Sorgente: `app/Actions/MakeAIRequestAction.php`. Esegue la richiesta `chat/completions` verso un endpoint compatibile OpenAI, con
tentativi e attesa crescente. È l'unico punto da cui `ClassifyTicketAction` e `SuggestSolutionsAction` parlano col provider e
l'ultima spiaggia di `RequestAssistantCompletionAction`.

Esiste una classe omonima in `Actions/Support/MakeAIRequestAction.php`: è un duplicato senza chiamanti, vedi «Backlog contract» nel
README.

## Input

- `execute(string $prompt, string $type, array $context = [], ?string $systemPrompt = null)`: il testo utente, un'etichetta per i
  log (`classification`, `solutions`, `assistant`…), metadati di log e un prompt di sistema facoltativo.
- Impliciti: `ai.openai_api_key` o `services.openai.api_key`; `AIConfigData` del tenant (`openai_base_url`, `timeout`, `retry_attempts`).

## Output

- `string`: il contenuto di `choices[0].message.content`; stringa vuota se la risposta è 2xx ma senza quel campo.

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
| AI-REQUEST-INV-1 | La chiave è `ai.openai_api_key` e, se manca, `services.openai.api_key`; mai il file del tenant. Va nell'intestazione `Authorization: Bearer`. | `AI-REQUEST-INV-1` |
| AI-REQUEST-INV-2 | La richiesta è un `POST` a `<openai_base_url>/chat/completions` (default `https://api.openai.com/v1`, sostituibile dal tenant) con modello `gpt-4`, `temperature` 0,3 e `max_tokens` 2000: modello e parametri non sono configurabili. | `AI-REQUEST-INV-2` |
| AI-REQUEST-INV-3 | I tentativi sono `retry_attempts` del tenant; esauriti, lancia `Exception('AI API request failed after N attempts')`. Tra un tentativo e il successivo si attende 2^n secondi (`Sleep::sleep`). | `AI-REQUEST-INV-3` |
| AI-REQUEST-INV-4 | Il prompt di sistema è `DEFAULT_SYSTEM_PROMPT` (ticket di amministrazioni pubbliche italiane, risposta JSON) se non se ne passa uno, altrimenti quello passato. | `AI-REQUEST-INV-4` |
| AI-REQUEST-INV-5 | Una risposta 2xx senza contenuto restituisce `''` senza errore e senza nuovo tentativo. | — (non dimostrato) |

Test: `tests/Unit/Actions/MakeAIRequestActionTest.php`, `MakeAIRequestActionSystemPromptTest.php`, `AiSettingsAreReadTest.php` (HTTP finto). Per INV-3 provano il numero di tentativi e il messaggio finale; l'attesa crescente non è provata.

## Effetti collaterali

Per ogni tentativo:

1. `POST` HTTPS al provider, con il testo dell'utente e il prompt di sistema nel corpo.
2. Su risposta non 2xx: `Log::warning('AI API request failed', …)` con `type`, tentativo, status, **corpo della risposta** e tutto
   `$context` (righe 90-95). Su eccezione: `Log::error('AI API request error', …)` con il messaggio e `$context` (righe 97-101).
3. Attesa `2^n` secondi se restano tentativi.

Nessuna scrittura sul database, nessuna cache.

## Errori

- `Exception` a tentativi esauriti. Una risposta 2xx non-array, o con `content` non stringa, fa scattare un `Assert` dentro il
  `try`: viene registrata e ritentata come un errore di rete.
- Le impostazioni del tenant con tipo sbagliato (`Assert` prima del ciclo) risalgono subito.

## Non fa

- Non sceglie il provider: parla solo con l'endpoint OpenAI-compatibile configurato; non applica `ai.free_models_only` e non ha
  cascata (quella è `RequestAssistantCompletionAction`).
- Non toglie dal prompt o dal contesto i dati personali.
- Non limita la frequenza delle chiamate e non fa streaming.
- Non verifica che la chiave non sia vuota: con chiave assente invia `Bearer ` vuoto e ritenta.

## Limiti noti (difetti da correggere, non da imitare)

- **Dati personali nei log**: `$context` di Classify e Suggest contiene titolo e descrizione della segnalazione, e il corpo della
  risposta del provider va nel log (righe 90-101). Un cittadino che descrive un problema con nome e indirizzo li lascia nel file di log.
- **Modello a pagamento fisso** (`gpt-4`, riga 58): il progetto prescrive modelli gratuiti e `free_models_only` vale `true` di
  default, ma questa Action non lo guarda.
- Ritenta anche su errori che non passano da soli (401 chiave sbagliata, 400): attese inutili prima del fallimento.
- Con `retry_attempts` ≤ 0 non parte nessuna richiesta e lancia subito «failed after 0 attempts».
- Il testo dell'eccezione e dei log è in inglese, scritto nel codice; il prompt di sistema storico è un testo italiano nel codice.
