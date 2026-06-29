<?php

declare(strict_types=1);

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Cache;
use Modules\AI\Support\AiJsonResponseDecoder;
use Webmozart\Assert\Assert;

use function Safe\json_encode;

/**
 * Servizio AI/ML per FixCity Platform
 */
class AIService
{
    private string $apiKey;

    private string $baseUrl;

    private int $timeout;

    private int $retryAttempts;

    private ?AIChatCompletionClient $chatClient = null;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key', '');
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $timeout = config('services.openai.timeout', 30);
        $retryAttempts = config('services.openai.retry_attempts', 3);

        Assert::string($apiKey, 'API key must be a string');
        Assert::string($baseUrl, 'Base URL must be a string');
        Assert::integer($timeout, 'Timeout must be an integer');
        Assert::integer($retryAttempts, 'Retry attempts must be an integer');

        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;
    }

    /**
     * @return array<string, mixed>
     */
    public function classifyTicket(string $title, string $description): array
    {
        $cacheKey = 'ai:classification:'.md5($title.$description);

        $result = Cache::remember($cacheKey, 3600, function () use ($title, $description) {
            $prompt = AIServicePromptBuilder::classification($title, $description);

            return $this->makeAIRequest($prompt, 'classification');
        });

        return AiJsonResponseDecoder::decodeObject($result);
    }

    /**
     * @return array<string, mixed>
     */
    public function suggestSolutions(string $title, string $description, string $category): array
    {
        $cacheKey = 'ai:solutions:'.md5($title.$description.$category);

        $result = Cache::remember($cacheKey, 1800, function () use ($title, $description, $category) {
            $prompt = AIServicePromptBuilder::solutions($title, $description, $category);

            return $this->makeAIRequest($prompt, 'solutions');
        });

        return AiJsonResponseDecoder::decodeObject($result);
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeSentiment(string $text): array
    {
        $cacheKey = 'ai:sentiment:'.md5($text);

        $result = Cache::remember($cacheKey, 1800, function () use ($text) {
            $prompt = AIServicePromptBuilder::sentiment($text);

            return $this->makeAIRequest($prompt, 'sentiment');
        });

        return AiJsonResponseDecoder::decodeObject($result);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function predictPriority(string $title, string $description, array $context = []): array
    {
        $contextStr = json_encode($context);
        $cacheKey = 'ai:priority:'.md5($title.$description.$contextStr);

        $result = Cache::remember($cacheKey, 1800, function () use ($title, $description, $context) {
            $prompt = AIServicePromptBuilder::priority($title, $description, $context);

            return $this->makeAIRequest($prompt, 'priority');
        });

        return AiJsonResponseDecoder::decodeObject($result);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tickets
     * @param  array<int, array<string, mixed>>  $agents
     * @return array<string, mixed>
     */
    public function optimizeRouting(array $tickets, array $agents): array
    {
        $jsonTickets = json_encode($tickets);
        $jsonAgents = json_encode($agents);
        $cacheKey = 'ai:routing:'.md5($jsonTickets.$jsonAgents);

        $result = Cache::remember($cacheKey, 900, function () use ($tickets, $agents) {
            $prompt = AIServicePromptBuilder::routing($tickets, $agents);

            return $this->makeAIRequest($prompt, 'routing');
        });

        return AiJsonResponseDecoder::decodeObject($result);
    }

    public function generateAutoResponse(string $ticketContent, string $category, string $priority): string
    {
        $cacheKey = 'ai:response:'.md5($ticketContent.$category.$priority);

        return Cache::remember($cacheKey, 1800, function () use ($ticketContent, $category, $priority) {
            $prompt = AIServicePromptBuilder::autoResponse($ticketContent, $category, $priority);

            return $this->makeAIRequest($prompt, 'response');
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $tickets
     * @return array<string, mixed>
     */
    public function analyzePatterns(array $tickets): array
    {
        $jsonTickets = json_encode($tickets);
        $cacheKey = 'ai:patterns:'.md5($jsonTickets);

        $result = Cache::remember($cacheKey, 3600, function () use ($tickets) {
            $prompt = AIServicePromptBuilder::patternAnalysis($tickets);

            return $this->makeAIRequest($prompt, 'patterns');
        });

        return AiJsonResponseDecoder::decodeObject($result);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function suggestImprovements(array $data): array
    {
        $jsonData = json_encode($data);
        $cacheKey = 'ai:improvements:'.md5($jsonData);

        $result = Cache::remember($cacheKey, 3600, function () use ($data) {
            $prompt = AIServicePromptBuilder::improvements($data);

            return $this->makeAIRequest($prompt, 'improvements');
        });

        return AiJsonResponseDecoder::decodeObject($result);
    }

    private function makeAIRequest(string $prompt, string $type): string
    {
        return $this->chatClient()->request($prompt, $type);
    }

    private function chatClient(): AIChatCompletionClient
    {
        if ($this->chatClient instanceof AIChatCompletionClient) {
            return $this->chatClient;
        }

        $this->chatClient = new AIChatCompletionClient(
            $this->apiKey,
            $this->baseUrl,
            $this->timeout,
            $this->retryAttempts,
        );

        return $this->chatClient;
    }
}
