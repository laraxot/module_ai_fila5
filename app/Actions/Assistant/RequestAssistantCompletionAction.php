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
 */
final class RequestAssistantCompletionAction
{
    use QueueableAction;

    public function __construct(
        private readonly OpenAiCompatibleRequestAction $request,
        private readonly MakeAIRequestAction $fallback,
    ) {}

    public function execute(string $systemPrompt, string $prompt): string
    {
        $settings = AIConfigData::make();

        foreach ($settings->assistant_providers as $name) {
            $provider = AiProviderEnum::tryFrom($name);
            $model = $settings->assistant_models[$name] ?? null;
            if ($provider === null || $model === null) {
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
}
