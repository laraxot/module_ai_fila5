<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Datas\PredictionData;
use Safe\DateTime;
use Spatie\QueueableAction\QueueableAction;
use Throwable;

use function Safe\json_decode;
use function Safe\preg_replace;

/**
 * Generate realistic predictions using AI for prediction markets.
 *
 * This action uses OpenAI API to generate realistic prediction market data
 * including title, description, content, category, tags, and market parameters.
 */
class GeneratePredictionsAction
{
    use QueueableAction;

    /**
     * Execute the AI prediction generation.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws Throwable
     */
    public function execute(string $topic, array $options = []): PredictionData
    {
        Log::info('Starting AI prediction generation', [
            'topic' => $topic,
            'options' => $options,
        ]);

        $prompt = $this->buildPrompt($topic, $options);

        $response = $this->callOpenAI($prompt);

        $data = $this->parseResponse($response);

        $this->validate($data);

        $predictionData = PredictionData::fromOpenAIResponse($data);

        Log::info('AI prediction generated successfully', [
            'topic' => $topic,
            'title' => $predictionData->title,
            'category' => $predictionData->category,
        ]);

        return $predictionData;
    }

    /**
     * Build the prompt for OpenAI.
     *
     * @param  array<string, mixed>  $options
     */
    private function buildPrompt(string $topic, array $options): string
    {
        $category = (string) ($options['category'] ?? 'generico');
        $language = (string) ($options['language'] ?? 'italiano');
        $today = now()->format('Y-m-d');

        return <<<PROMPT
Genera una predizione realistica e dettagliata per un prediction market su: {$topic}

Categoria: {$category}
Lingua: {$language}
Data odierna: {$today}

Includi TUTTI i seguenti campi in formato JSON valido:

{
  "title": "Titolo accattivante e chiaro in {$language}",
  "description": "Descrizione chiara e concisa in 2-3 frasi",
  "content": "Contenuto dettagliato e ben strutturato di 300-500 parole che spiega il contesto, i criteri di risoluzione, e le informazioni rilevanti",
  "excerpt": "Estratto breve di 1-2 frasi per anteprima",
  "category": "Nome della categoria più appropriata (es: Politica, Sport, Economia, Tecnologia, Scienza, Intrattenimento)",
  "tags": ["tag1", "tag2", "tag3", "tag4"],
  "closed_at": "Data di chiusura YYYY-MM-DD (tra 30-60 giorni da oggi)",
  "ends_at": "Data di risoluzione YYYY-MM-DD (tra 60-90 giorni da oggi)",
  "liquidity_parameter": 0.5,
  "stocks_count": 1000,
  "is_wagerable": true,
  "content_block": "Contenuto principale formattato in blocchi",
  "sidebar_block": "Informazioni laterali utili",
  "footer_block": "Note aggiuntive e riferimenti"
}

REGOLE IMPORTANTI:
1. Restituisci SOLO il JSON, niente altro testo
2. Assicurati che il JSON sia valido e ben formattato
3. Usa date realistiche e coerenti con l'argomento
4. I tags devono essere rilevanti e specifici per l'argomento
5. La descrizione deve essere chiara sui criteri di risoluzione
6. Il contenuto deve essere neutrale e informativo
7. Categoria deve essere una delle seguenti: Politica, Sport, Economia, Tecnologia, Scienza, Intrattenimento, Salute, Ambiente

Esempio di argomento: "Elezioni politiche Italia 2026"
Esempio di titolo: "Chi vincerà le elezioni politiche in Italia nel 2026?"
Esempio di descrizione: "Questa predizione riguarda il partito che otterrà la maggioranza relativa alle elezioni politiche italiane del 2026."

PROMPT;
    }

    /**
     * Call OpenAI API.
     */
    private function callOpenAI(string $prompt): string
    {
        $apiKey = (string) (config('services.openai.api_key') ?? '');
        $model = config('ai.model', 'gpt-3.5-turbo-instruct');
        $temperature = config('ai.temperature', 0.7);
        $maxTokens = config('ai.max_tokens', 1500);

        Log::debug('Calling OpenAI API', [
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->post('https://api.openai.com/v1/completions', [
                'model' => $model,
                'prompt' => $prompt,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'top_p' => 1.0,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0,
            ]);

        if ($response->failed()) {
            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('OpenAI API request failed: '.$response->body());
        }

        /** @var array<string, mixed>|null $data */
        $data = $response->json();
        $choices = is_array($data) ? ($data['choices'] ?? null) : null;
        $firstChoice = is_array($choices) ? ($choices[0] ?? null) : null;
        $text = is_array($firstChoice) ? ($firstChoice['text'] ?? '') : '';
        $result = is_string($text) ? $text : '';

        $usage = is_array($data) ? ($data['usage'] ?? null) : null;
        $totalTokens = is_array($usage) ? ($usage['total_tokens'] ?? 0) : 0;

        Log::debug('OpenAI API response received', [
            'tokens_used' => $totalTokens,
        ]);

        return trim($result);
    }

    /**
     * Parse the OpenAI response.
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    private function parseResponse(string $response): array
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/^```json\s*/i', '', $response);
        $response = preg_replace('/^```\s*/i', '', $response);
        $response = preg_replace('/\s*```$/', '', $response);
        $response = trim($response ?? '');

        Log::debug('Parsing OpenAI response', [
            'response_length' => strlen($response),
        ]);

        /** @var array<string, mixed> $data */
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR) ?? [];

        return $data;
    }

    /**
     * Validate the generated data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \InvalidArgumentException
     */
    private function validate(array $data): void
    {
        Log::debug('Validating prediction data', ['data' => $data]);

        $required = ['title', 'description', 'content', 'category', 'tags', 'closed_at'];

        foreach ($required as $field) {
            if (! isset($data[$field]) || $data[$field] === '') {
                Log::error('Missing required field', ['field' => $field]);
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate dates
        if (isset($data['closed_at'])) {
            try {
                $closedAtStr = is_string($data['closed_at']) ? $data['closed_at'] : (string) $data['closed_at'];
                $closedAt = new DateTime($closedAtStr);
                $today = new DateTime;

                if ($closedAt <= $today) {
                    throw new \InvalidArgumentException('closed_at must be in the future');
                }
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid closed_at date format');
            }
        }

        // Validate tags is array
        if (! is_array($data['tags'])) {
            throw new \InvalidArgumentException('tags must be an array');
        }

        // Validate liquidity_parameter is between 0 and 1
        if (isset($data['liquidity_parameter'])) {
            $liquidity = (float) $data['liquidity_parameter'];
            if ($liquidity < 0 || $liquidity > 1) {
                throw new \InvalidArgumentException('liquidity_parameter must be between 0 and 1');
            }
        }

        Log::debug('Validation passed');
    }
}
