# Modello dati — dominio prospecting e agenti AG-UI

Tutte le tabelle del modulo sono additive e migrate in `database/migrations/` (date 2026-11-27 per il dominio prospect).
Modelli in `app/Models/`, tutti estendono `XotBaseModel`.

## Prospecting

### `ai_prospects` — `AiProspect`

| Colonna | Note |
|---|---|
| `public_id` string(64) | identità deterministica |
| `workspace_id` string | isolamento tenant |
| `name`, `domain`, `website`, `city`, `region`, `country_code`, `industry`, `phone` | nullable |
| `source`, `source_url`, `source_at` | provenienza |
| `raw_data` json, `summary` text | dati normalizzati + sintesi |
| `status` string default `new` | → `AiProspectStatusEnum` |
| `last_reviewed_at`, `last_contact_attempt_at` | timestamp workflow |

Unique: `(workspace_id, public_id)`.

**Enum `AiProspectStatusEnum`**: `new`, `qualified`, `contacted`, `cold_lost`, `won`.

### `ai_prospect_signals` — `AiProspectSignal`

FK `ai_prospect_id` cascade. Campi: `source` (→ `AiProspectSignalSourceEnum`:
`csv`, `osm`, `public_listing`, `website_signals`, `email_verification`),
`source_url`, `source_at`, `quality_source` (`high|medium|low`, default low),
`confidence` 0–100, `score` 0–100, `breakdown` json `{field: {value, score, reason}}`,
`note`.

### `ai_prospect_drafts` — `AiProspectDraft`

FK `ai_prospect_id`. `subject`, `body`, `channel` (default `email`),
`status` → `AiProspectDraftStatusEnum`: `draft`, `pending_review`, `reviewed`,
`sent`, `suppressed`. `lawful_basis` json `{basis, evidence}`,
`personalization` json, `reviewed_by_user_id`/`reviewed_at`,
`sent_at`, `suppression_reason`.

### `ai_suppressions` — `AiSuppression`

`public_id`, `workspace_id` (index), `email_hash` (mai email in chiaro),
`reason` → `AiSuppressionReasonEnum`: `do_not_contact`, `unsubscribed`,
`bounced`, `invalid_email`, `gdpr_request`; `note`, `suppressed_by_user_id`,
`suppressed_at`. Unique `(workspace_id, public_id)`.

## Assistente e azioni (pre-prospecting)

| Tabella | Modello | Note |
|---|---|---|
| `ai_threads` / `ai_messages` | `AiThread`, `AiMessage` | chat assistente/pannello; `AiMessageRoleEnum`: user, assistant, tool, system |
| `ai_action_proposals` | `AiActionProposal` | proposte azione con `public_id` uuid, `type`, `payload`, `preview`, `status` → `AiActionProposalStatusEnum`: `pending`, `cancelled`, `confirmed`, `executed`, `failed`; `confirmed_by_user_id`, `executed_at`, `result`, `error` |
| `ai_tool_logs` | `AiToolLog` | log tool; `AiToolLogStatusEnum`: ok, error |
| `ai_knowledge_entries` | `AiKnowledgeEntry` | base di conoscenza |

## Agenti AG-UI (neuron-ai)

| Tabella | Modello | Note |
|---|---|---|
| `ai_agent_messages` | `AiAgentMessage` | cronologia per thread: `thread_id`, `message_id` (unique per thread), `role`, `content` json, `meta` json, `archived_at` |
| `ai_agent_runs` | `AiAgentRun` | stato run in pausa per approvazione tool: `partition`+`key` unique (ascii bin), `value` longText — **senza cast**, cancellazioni fisiche (regole neuron-ai) |

Vedi `docs/agents.md`: `ai_threads`/`ai_messages` restano dell'assistente;
le tabelle agent dedicate servono messaggi completi (tool call, risultati) e
resume dei run dopo approvazione.

## Provider

`AiProviderEnum`: `openrouter`, `groq`, `local`. Scelta provider:
`Actions/Agent/ResolveAgentProviderAction` (primo fornitore con chiave in
`assistant_providers`: Groq, poi OpenRouter, via `OpenAILike`).
