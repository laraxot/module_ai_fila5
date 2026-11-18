<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Assistant;

use Illuminate\Support\Facades\Log;
use Modules\AI\Actions\MakeAIRequestAction;
use Modules\AI\Actions\Vision\OpenAiCompatibleRequestAction;
use Modules\AI\Datas\AIConfigData;
use Modules\AI\Enums\AiProviderEnum;
use Modules\AI\Exceptions\AiRequestException;
use Spatie\QueueableAction\QueueableAction;

/**
 * Chiede la risposta dell'assistente ai fornitori configurati, in cascata.
 *
 * Ordine e modelli da AIConfigData (`assistant_providers`, `assistant_models`): di default Groq,
 * poi OpenRouter. Un fornitore senza chiave o che fallisce passa la mano al successivo; se nessuno
 * risponde si usa MakeAIRequestAction (OpenAI), come prima di questa Action.
 * La risposta e' chiesta in JSON (`response_format: json_object`), come vuole il prompt dell'assistente.
 *
 * Contratto di comportamento (Modules/Xot/docs/rules/action-behavioral-contract.md):
 * - Input: prompt di sistema e prompt dell'utente.
 * - Output: il testo della risposta (JSON, come chiede il prompt) del primo fornitore che risponde.
 * - Invarianti: con `free_models_only` mai un modello a pagamento; ogni richiesta chiede `response_format: json_object` e
 *   almeno un messaggio nomina JSON (JSON_INSTRUCTION), altrimenti Groq e OpenAI rispondono 400.
 * - Effetti collaterali: richieste HTTP ai fornitori in ordine (`assistant_providers`); un avviso nel log per ogni fornitore
 *   saltato o in errore.
 * - Errori: un fornitore senza chiave, in errore o senza contenuto passa la mano al successivo; alla fine prova OpenAI
 *   (MakeAIRequestAction), le cui eccezioni arrivano al chiamante.
 * - Non fa: non costruisce il prompt (BuildAssistantPromptAction); non interpreta il JSON della risposta; non salva la
 *   conversazione.
 */
final class RequestAssistantCompletionAction
{
    use QueueableAction;

    public function __construct(
        private readonly OpenAiCompatibleRequestAction $request,
        private readonly MakeAIRequestAction $fallback,
    ) {}

    /** Aggiunta al prompt di sistema quando nessun messaggio nomina JSON (vedi withJsonInstruction()). */
    public const string JSON_INSTRUCTION = 'Reply with a single valid JSON object.';

    public function execute(string $systemPrompt, string $prompt): string
    {
        $settings = AIConfigData::make();
        $systemPrompt = $this->withJsonInstruction($systemPrompt, $prompt);

        foreach ($settings->assistant_providers as $name) {
            $provider = AiProviderEnum::tryFrom($name);
            $model = $settings->assistant_models[$name] ?? null;
            if ($provider === null || $model === null) {
                continue;
            }
            // Solo modelli gratuiti (ai.free_models_only): un modello a pagamento configurato per errore si salta.
            if ($settings->free_models_only && ! $provider->isFreeModel($model)) {
                continue;
            }

            try {
                $json = $this->request->execute($provider, '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 1000,
                    'response_format' => ['type' => 'json_object'],
                ], $settings->assistant_timeout);
            } catch (AiRequestException $exception) {
                Log::warning('AI assistant provider failed', ['provider' => $name, 'error' => $exception->getMessage()]);

                continue;
            }

            $content = data_get($json, 'choices.0.message.content');
            if (is_string($content) && trim($content) !== '') {
                return $content;
            }

            Log::warning('AI assistant provider returned no content', ['provider' => $name]);
        }

        return $this->fallback->execute($prompt, 'assistant', [], $systemPrompt);
    }

    /**
     * Con `response_format: json_object` Groq (come OpenAI) risponde HTTP 400 se nessun messaggio contiene la parola
     * «json»: il fornitore verrebbe saltato a ogni chiamata. Il prompt dell'assistente la contiene gia'; quello di un
     * modulo chiamante potrebbe non contenerla.
     */
    private function withJsonInstruction(string $systemPrompt, string $prompt): string
    {
        if (stripos($systemPrompt.' '.$prompt, 'json') !== false) {
            return $systemPrompt;
        }

        return trim($systemPrompt."\n\n".self::JSON_INSTRUCTION);
    }
}
