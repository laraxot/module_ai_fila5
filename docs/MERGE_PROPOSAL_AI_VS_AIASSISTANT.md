---
title: "AI — Intelligenza Artificiale Base"
description: "Modulo per l'integrazione di funzionalità di intelligenza artificiale e servizi AI"
module: "AI"
alias: "ai"
version: "1.0.0"
priority: 0
active: true
status: "domain-ai-base"
author: "Team Laraxot"
license: "Proprietary"
php_version: "^8.1"
core_version: "10.0"
dependencies: ["Xot", "User"]
extends: []
extended_by: 0
documentation_date: "2026-05-27"
merge_proposal: "unite-with-aiassistant"
merge_proposal_status: "pending-analysis"
---

# AI — Intelligenza Artificiale Base

## Scopo

AI è il modulo base per l'integrazione di funzionalità di intelligenza artificiale e servizi AI nell'ecosistema Laraxot. Fornisce l'infrastruttura di base per prompt, predizioni, chiamate a DS4 (DeepSeek V4), proposte di azioni e context compression.

## Religione

La religione di AI si esprime in:

- **"Tutta la logica AI in un unico posto"**: il dominio AI deve essere centralizzato in un solo modulo
- **"Azioni, non servizi"**: la logica di business vive in `Actions/` con metodo `execute()` e `use QueueableAction`
- **"XotBase è il fondamento"**: ogni modello estende `XotBaseModel`, ogni risorsa `XotBaseResource`
- **"DS4 è il motore di inferenza"**: le chiamate a DeepSeek V4 passano per `ChatDs4Action` e `GenerateDs4Action`
- **"Le proposte di azione sono il ponte"**: `AiActionProposal` è il modello che collega AI a WorkOrder e altri moduli
- **"Prompt engineering è scienza"**: `BuildAIPromptAction`, `AIPromptTemplates` gestiscono la costruzione dei prompt

## Filosofia

AI crede che **l'intelligenza artificiale debba essere un servizio trasparente**, non una scatola magica. Le azioni sono il contratto: un modulo che ha bisogno di AI chiama un'Action, non istanzia un client direttamente.

La filosofia è **AI come layer di astrazione**: `AiActionProposal` è il modello che permette a qualsiasi modulo (WorkOrder, Intervention, Quotation) di proporre azioni AI e ricevere conferme. È un ponte tra il mondo AI e il mondo business.

## Politica

- **Un solo modulo per l'AI**: `AI` è l'unico modulo che gestisce l'intelligenza artificiale
- **Proposte confermate dagli umani**: le azioni AI generano proposte, gli umani confermano
- **DS4 come provider primario**: DeepSeek V4 è il motore di inferenza principale
- **Streaming per chat**: le risposte AI possono essere in streaming (`AiChatStreamController`)
- **Sicurezza ai prompt**: `PromptInjectionGuard` protegge da injection attacks

## Zen

> **"L'AI suggerisce, l'umano decide."**

Lo Zen di AI è il **partner umano-AI**: l'intelligenza artificiale propone, l'essere umano conferma. Nessuna azione AI viene eseguita senza approvazione umana esplicita.

## Perché esiste

Ogni modulo dell'ecosistema ha bisogno di funzionalità AI (classificazione, suggerimenti, predizioni). AI esiste per centralizzare questa necessità in un unico modulo riutilizzabile.

## Superpoteri

- **Prompt building**: `BuildAIPromptAction`, `AIPromptTemplates`, `BuildTicketClassificationPromptAction`
- **Predizioni**: `GeneratePredictionsAction`, `GeneratePredictionDraftsAction`
- **DS4 integration**: `ChatDs4Action`, `GenerateDs4Action`
- **Proposte azioni**: `CreateAiActionProposalAction`, `ConfirmAiActionProposalAction`
- **Context compression**: `ContextCompressorAction` per ridurre il contesto
- **Filament dashboard**: `Dashboard`, `FineTuning`, `Completion`

## Cosa Mancherebbe (Gap Analysis)

| Gap | Severità | Suggerimento |
|-----|----------|--------------|
| **Duplicazione con AiAssistant** | Critica | Unire con AiAssistant in un unico modulo `AI` |
| Nessun sistema di caching per risposte AI | Alta | Aggiungere `AiResponseCache` per evitare chiamate duplicate |
| Manca supporto multi-provider | Alta | Aggiungere provider abstraction (OpenAI, Anthropic, local) |
| Nessun sistema di rate limiting | Media | Aggiungere `AiRateLimiter` per evitare abuse |
| Manca analisi dei costi per provider | Media | Aggiungere `AiCostTracker` per tracciare spese |
| Nessun sistema di fallback tra provider | Media | Aggiungere `AiFallbackChain` per resilienza |
| Manca streaming bidirezionale | Bassa | Aggiungere supporto SSE per streaming real-time |
| Nessun sistema di eval/benchmarking | Bassa | Aggiungere `AiBenchmark` per valutare provider |

## Proposta di Merge con AiAssistant

### Perché Unire

1. **Stesso dominio**: entrambi i moduli gestiscono intelligenza artificiale
2. **Codice duplicato**: `AiActionProposal` model e Actions sono in entrambi
3. **Frammentazione**: due moduli per lo stesso scopo creano confusione
4. **Dipendenza circolare**: AiAssistant dipende da AI, ma AI ha funzionalità destinate ad AiAssistant

### Vantaggi dell'Unione

- **Un solo modulo** per tutta la logica AI
- **Zero duplicazione**: modelli e azioni in un unico posto
- **Configurazione centralizzata**: un solo file di configurazione
- **Testing semplificato**: una sola suite di test
- **Manutenibilità**: bug fix e feature in un solo posto

### Struttura Proposta Post-Merge

```
AI (modulo unificato)
├── app/
│   ├── Actions/
│   │   ├── Prompt/           (da AI)
│   │   ├── Prediction/       (da AI)
│   │   ├── Ds4/              (da AI)
│   │   ├── Cast/             (da AI)
│   │   ├── CreateAiActionProposalAction.php  (unificato)
│   │   ├── ExecuteAiActionProposalAction.php (da AiAssistant)
│   │   └── ConfirmAiActionProposalAction.php (da AI)
│   ├── Chat/                 (da AiAssistant)
│   ├── Http/Controllers/     (da AiAssistant)
│   ├── Import/               (da AiAssistant)
│   ├── Llm/                  (da AiAssistant)
│   ├── Lookup/               (da AiAssistant)
│   ├── Models/
│   │   ├── AiActionProposal.php  (unificato)
│   │   ├── AiThread.php          (da AiAssistant)
│   │   ├── AiToolLog.php         (da AiAssistant)
│   │   └── AiMessage.php         (da AiAssistant)
│   ├── Neuron/               (da AiAssistant)
│   ├── Reports/              (da AiAssistant)
│   ├── Security/             (da AiAssistant)
│   ├── Speech/               (da AiAssistant)
│   ├── Filament/
│   │   ├── Resources/AiActionProposalResource/
│   │   └── Pages/            (Dashboard, Chat, FineTuning, Completion)
│   └── Providers/
│       ├── AIServiceProvider.php
│       └── AdminPanelProvider.php
├── config/
│   ├── ai.php              (da AI)
│   ├── ai_security.php     (da AiAssistant)
│   └── config.php          (unificato)
```

### Azioni di Migrazione

1. Spostare `AiAssistant/app/Chat/` → `AI/app/Chat/`
2. Spostare `AiAssistant/app/Http/Controllers/` → `AI/app/Http/Controllers/`
3. Spostare `AiAssistant/app/Llm/` → `AI/app/Llm/`
4. Spostare `AiAssistant/app/Lookup/` → `AI/app/Lookup/`
5. Spostare `AiAssistant/app/Neuron/` → `AI/app/Neuron/`
6. Spostare `AiAssistant/app/Reports/` → `AI/app/Reports/`
7. Spostare `AiAssistant/app/Security/` → `AI/app/Security/`
8. Spostare `AiAssistant/app/Speech/` → `AI/app/Speech/`
9. Spostare `AiAssistant/app/Import/` → `AI/app/Import/`
10. Unificare `AiActionProposal` model (rimuovere duplicato)
11. Unificare `CreateAiActionProposalAction` (rimuovere duplicato)
12. Unificare configurazioni in `ai.php` + `ai_security.php`
13. Rimuovere modulo `AiAssistant`

---

*Documento generato secondo le convenzioni del progetto — modulo `AI` — data 2026-05-27*
