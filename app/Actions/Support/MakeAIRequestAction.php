<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Support;

use Modules\AI\Services\AIChatCompletionClient;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

class MakeAIRequestAction
{
    use QueueableAction;

    public function __construct(
        private readonly ?string $prompt = null,
        private readonly ?string $type = null,
    ) {
    }

    public function execute(?string $prompt = null, ?string $type = null): string
    {
        $prompt = $prompt ?? $this->prompt ?? '';
        $type = $type ?? $this->type ?? '';
        $apiKey = config('ai.openai_api_key', '');
        $baseUrl = config('ai.openai_base_url', 'https://api.openai.com/v1');
        $timeout = config('ai.timeout', 30);
        $retryAttempts = config('ai.retry_attempts', 3);

        Assert::string($apiKey, 'API key must be a string');
        Assert::string($baseUrl, 'Base URL must be a string');
        Assert::integer($timeout, 'Timeout must be an integer');
        Assert::integer($retryAttempts, 'Retry attempts must be an integer');

        $client = new AIChatCompletionClient($apiKey, $baseUrl, $timeout, $retryAttempts);

        return $client->request($prompt, $type);
    }

    public function handle(): string
    {
        return $this->execute();
    }
}
