---
title: "Sei prompt che non hanno un successore"
type: reference
tags: [ai, prompt, no-services-rule, archivio]
created: 2026-11-20
updated: 2026-11-20
qmd: "prompt AI senza successore sentiment priority routing autoResponse patternAnalysis improvements"
issues: [247]
discussions: []
---

# Sei prompt che non hanno un successore

`Modules/AI/app/Services/` conteneva cinque classi che violavano la regola «no
Services» e che nessuno chiamava. Quattro erano copie morte: il loro lavoro vive
in `Actions/MakeAIRequestAction`, `Actions/AiJsonResponseDecoderAction`,
`Datas/AIPromptTemplates` e nelle due Action `ClassifyTicketAction` /
`SuggestSolutionsAction`.

La quinta, `AIServicePromptBuilder`, aveva **otto** prompt. Due hanno un
successore: `classification` e `solutions`, oggi in
`Actions/Prompt/BuildAIPromptAction`, che accetta solo quei due nomi
(`default => throw new InvalidArgumentException`).

Gli altri sei non esistono in nessun altro posto. Cercate le loro frasi
caratteristiche su tutto il repository: unico risultato il file stesso. Quindi
sono conservati qui prima di cancellare il file, perche' cancellare codice morto
e' una cosa e buttare sei prompt scritti in italiano, con i loro schemi JSON, e'
un'altra.

Attenzione a un falso successore: `SentimentAction` e
`Actions/Sentiment/Analyze{Basic,Transformers}SentimentAction` **non** sostituiscono
`sentiment`. Fanno un'altra cosa — parole chiave inglesi e transformers, con
output `label` + `score` (`Datas/SentimentData`) — e non interrogano nessun LLM.

Chi decide di riportarne uno in vita scrive una Action per caso d'uso, con
`execute()` tipizzato, e la collega a un consumatore: una Action senza chiamanti
e' codice morto con un nome migliore.

## 1. sentiment

Un testo di un cittadino, sei campi in uscita.

```
Analizza il sentiment del seguente testo di un cittadino:

{$text}

Rispondi in formato JSON:
{
  "sentiment": "positive|negative|neutral",
  "emotion": "soddisfazione|frustrazione|preoccupazione|rabbia|speranza",
  "confidence": 0.85,
  "key_phrases": ["frase1", "frase2"],
  "urgency_level": "low|medium|high|critical",
  "recommended_response_tone": "professionale|empatico|rassicurante|decisivo"
}
```

## 2. priority

Titolo, descrizione e un contesto serializzato in JSON.

```
Predici la priorità di questo ticket:

Titolo: {$title}
Descrizione: {$description}
Contesto: {$contextStr}

Considera:
- Impatto sulla sicurezza pubblica
- Numero di cittadini coinvolti
- Urgenza temporale
- Complessità di risoluzione
- Risorse disponibili

Rispondi in formato JSON:
{
  "priority": "low|medium|high|urgent|critical",
  "confidence": 0.90,
  "reasoning": "motivazione dettagliata",
  "estimated_resolution_time": "1-2 giorni",
  "required_escalation": true|false,
  "risk_factors": ["fattore1", "fattore2"]
}
```

## 3. routing

Ticket e agenti, entrambi serializzati in JSON.

```
Ottimizza l'assegnazione di questi ticket agli agenti disponibili:

Ticket: {$ticketsStr}
Agenti: {$agentsStr}

Considera:
- Competenze degli agenti
- Carico di lavoro attuale
- Specializzazione per categoria
- Disponibilità temporale
- Precedenti performance

Rispondi in formato JSON:
{
  "assignments": [
    {
      "ticket_id": 123,
      "agent_id": 456,
      "reason": "motivazione assegnazione",
      "estimated_completion": "2024-01-15",
      "confidence": 0.85
    }
  ],
  "unassigned_tickets": [789],
  "overload_warnings": ["agent1 ha troppi ticket"],
  "efficiency_score": 0.92
}
```

Lo schema era `AIServicePromptTemplates::ROUTING_JSON`, che sopravvive
byte-identico in `Modules/AI/app/Datas/AIPromptTemplates.php` — a sua volta senza
chiamanti.

## 4. autoResponse

L'unico che non chiede JSON: vuole prosa da mandare al cittadino.

```
Genera una risposta automatica professionale per questo ticket:

Contenuto: {$ticketContent}
Categoria: {$category}
Priorità: {$priority}

La risposta deve essere:
- Professionale ma amichevole
- Rassicurante per il cittadino
- Specifica per la categoria
- Adatta alla priorità
- In italiano corretto
- Lunga 2-3 paragrafi

Rispondi solo con il testo della risposta, senza formattazione aggiuntiva.
```

## 5. patternAnalysis

```
Analizza i pattern in questi ticket per identificare:

Ticket: {$ticketsStr}

Identifica:
- Trend temporali
- Aree geografiche problematiche
- Categorie più frequenti
- Pattern stagionali
- Correlazioni tra fattori
- Opportunità di miglioramento

Rispondi in formato JSON:
{
  "temporal_trends": {
    "peak_hours": ["9-11", "14-16"],
    "peak_days": ["lunedì", "martedì"],
    "seasonal_patterns": {"estate": "+20%"}
  },
  "geographic_hotspots": [
    {"area": "centro", "count": 45, "trend": "increasing"}
  ],
  "category_insights": {
    "most_common": "infrastruttura",
    "growing": "ambiente",
    "declining": "trasporti"
  },
  "recommendations": [
    "Aumentare personale nelle ore di picco",
    "Focus su area centro"
  ]
}
```

Schema: `AIServicePromptTemplates::PATTERN_JSON`.

## 6. improvements

```
Suggerisci miglioramenti per il servizio di gestione ticket basandoti su questi dati:

Dati: {$dataJson}

Fornisci suggerimenti per:
- Processi operativi
- Tecnologie
- Formazione personale
- Comunicazione cittadini
- Metriche di performance

Rispondi in formato JSON:
{
  "process_improvements": [
    {
      "area": "assegnazione ticket",
      "suggestion": "Implementare sistema di priorità dinamica",
      "impact": "high",
      "effort": "medium"
    }
  ],
  "technology_upgrades": [
    {
      "technology": "AI routing",
      "description": "Sistema di assegnazione automatica",
      "benefits": ["efficienza", "soddisfazione"],
      "cost_estimate": "€50k"
    }
  ],
  "training_recommendations": [
    {
      "role": "operatori",
      "topics": ["comunicazione", "tecniche risoluzione"],
      "format": "workshop",
      "duration": "2 giorni"
    }
  ]
}
```

Schema: `AIServicePromptTemplates::IMPROVEMENTS_JSON`. Nel codice la parte da
«Fornisci suggerimenti per:» in giu' stava dentro la costante, non nel builder:
il prompt si componeva come intro + dati + schema.
