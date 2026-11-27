---
title: "Contratto — SaveKnowledgeEntryAction"
type: contract
<<<<<<< .merge_file_ni976o
action: "Modules\AI\Actions\Assistant\SaveKnowledgeEntryAction"
tags: [contract, action, ai, assistente, knowledge-base]
created: 2026-11-24
updated: 2026-11-24
=======
action: "Modules\\AI\\Actions\\Assistant\\SaveKnowledgeEntryAction"
module: AI
status: active
tags: [contract, action, ai, assistente, conoscenza]
created: 2026-11-26
updated: 2026-11-26
related:
  - request-assistant-completion-action.md
>>>>>>> .merge_file_2WKWkZ
---

# SaveKnowledgeEntryAction

Contratto di comportamento (regola: `Modules/Xot/docs/rules/action-behavioral-contract.md`).
<<<<<<< .merge_file_ni976o
Sorgente: `app/Actions/Assistant/SaveKnowledgeEntryAction.php`.

## Input

- `collection` (la base di conoscenza di un progetto o cliente), `question`, `answer`.
- Facoltativi: `tags` (lista), `id` della voce da aggiornare, `isActive` (default `true`).

## Output

- La voce `AiKnowledgeEntry` salvata.
=======
Sorgente: `app/Actions/Assistant/SaveKnowledgeEntryAction.php`. Crea o aggiorna una voce (domanda e risposta) della base di
conoscenza di una `collection`, da cui l'assistente pesca le risposte.

Ripristinato il 2026-11-26 dopo la cancellazione nel repository del modulo (commit `2471438`). Stato d'uso: nessun codice di
produzione la chiama (`rg SaveKnowledgeEntryAction`: solo la classe e il suo test).

## Input

- `execute(string $collection, string $question, string $answer, ?array $tags = null, ?int $id = null, bool $isActive = true)`.
- `$collection` separa le basi di conoscenza (un progetto o un cliente ciascuna). `$id`: la voce da aggiornare, se già nota.

## Output

- Il `AiKnowledgeEntry` salvato.
>>>>>>> .merge_file_2WKWkZ

## Invarianti

| ID | Regola | Group Pest |
|---|---|---|
<<<<<<< .merge_file_ni976o
| AI-KNOWLEDGE-INV-1 | Domanda o risposta vuote (dopo `trim`) → `ValidationException` prima di toccare il database. | `AI-KNOWLEDGE-INV-1` |
| AI-KNOWLEDGE-INV-2 | Con `id`, la voce si aggiorna solo se appartiene alla stessa `collection`; altrimenti `ValidationException` su `id`. | — |
| AI-KNOWLEDGE-INV-3 | Senza `id`, una voce con la stessa domanda nella `collection` si aggiorna invece di duplicarsi. | — |
| AI-KNOWLEDGE-INV-4 | `tags` nullo conserva i tag esistenti; una lista li sostituisce, senza voci vuote. | — |

Test: `tests/Unit/Assistant/SaveKnowledgeEntryActionTest.php` (solo INV-1; le altre richiedono la tabella `ai_knowledge_entries`).

## Effetti collaterali

- Un INSERT o UPDATE su `ai_knowledge_entries`.

## Errori

- `ValidationException` (INV-1, INV-2); errori del database al chiamante.

## Non fa

- Non verifica chi chiama: l'autorizzazione è del chiamante.
- Non calcola embedding né indicizza: la ricerca (`SearchKnowledgeEntriesAction` → `RankKnowledgeEntriesAction`) legge le voci attive della `collection` a ogni domanda.
- Non cancella voci (per spegnerne una: `isActive = false`).
=======
| AI-KNOWLEDGE-INV-1 | Domanda o risposta vuote (dopo `trim`) lanciano `ValidationException` prima di toccare il database. | `AI-KNOWLEDGE-INV-1` |
| AI-KNOWLEDGE-INV-2 | Con `$id` si aggiorna la voce solo se appartiene alla stessa `collection`; altrimenti `ValidationException` sul campo `id`: un id qualsiasi non permette di modificare la base di conoscenza di un altro progetto. | — (non dimostrato) |
| AI-KNOWLEDGE-INV-3 | Senza `$id`, una voce con la stessa domanda nella stessa `collection` viene aggiornata e non duplicata. | — (non dimostrato) |
| AI-KNOWLEDGE-INV-4 | `$tags` a `null` conserva i tag esistenti; altrimenti i tag vuoti si scartano e l'elenco si reindicizza. | — (non dimostrato) |

## Effetti collaterali

- Una `SELECT` per trovare la voce, poi un `INSERT` o un `UPDATE` su `ai_knowledge_entries`.
- Nessuna notifica, nessun log, nessuna chiamata di rete.

## Errori

- `ValidationException` per domanda o risposta vuote (campo `question`) e per `$id` non trovato nella collection (campo `id`).
- Errori di database risalgono al chiamante.

## Non fa

- Non controlla chi la chiama né che abbia accesso alla `collection`: l'autorizzazione è del chiamante.
- Non normalizza la domanda: il confronto per «stessa domanda» è un `where question = …` del database, quindi maiuscole e punteggiatura seguono la collation e la grafia.
- Non rimuove voci né le disattiva da sola (`$isActive` lo decide il chiamante).
- Non controlla che la risposta non contenga dati personali.

## Limiti noti (difetti da correggere, non da imitare)

- Con `$id` e senza `$id` il comportamento cambia in modo silenzioso: passare `$id` di una voce di un'altra collection dà errore,
  passare la stessa domanda senza `$id` sovrascrive la risposta esistente.
- I messaggi di `ValidationException` («Domanda e risposta sono obbligatorie.», «Voce non trovata in questa base di conoscenza.»)
  sono testi italiani scritti nel codice, fuori dai file di lingua.
>>>>>>> .merge_file_2WKWkZ
