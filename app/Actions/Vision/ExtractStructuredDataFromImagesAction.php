<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Vision;

use Modules\AI\Datas\AIConfigData;
use Modules\AI\Exceptions\AiRequestException;
use Safe\Exceptions\JsonException;
use Safe\Exceptions\PcreException;
use Spatie\QueueableAction\QueueableAction;

use function Safe\json_decode;
use function Safe\preg_replace;

/**
 * Legge una o piu' immagini e restituisce i dati richiesti come array conforme a uno schema JSON.
 *
 * Agnostica: non sa cosa c'e' nelle immagini. Il modulo che la usa passa istruzioni e schema
 * (es. Restaurant: le pagine di un menu cartaceo -> sezioni e piatti).
 *
 * Endpoint compatibile OpenAI (`/chat/completions`), di default OpenRouter: immagini come
 * `image_url` con data URI, testo prima delle immagini, `response_format` json_schema strict e
 * `provider.require_parameters` perche' OpenRouter instradi solo verso chi rispetta lo schema.
 */
class ExtractStructuredDataFromImagesAction
{
    use QueueableAction;

    public function __construct(private readonly ImageDataUriAction $dataUri) {}

    /**
     * @param  list<string>  $imagePaths  file su disco (png, jpeg, webp, gif)
     * @param  array<string, mixed>  $schema  JSON Schema dell'oggetto atteso
     * @return array<string, mixed>
     *
     * @throws AiRequestException
     */
    public function execute(array $imagePaths, string $instructions, string $schemaName, array $schema, ?string $model = null): array
    {
        if ($imagePaths === []) {
            throw new AiRequestException('Nessuna immagine da leggere.');
        }

        $config = AIConfigData::make();
        $content = [['type' => 'text', 'text' => $instructions]];
        foreach ($imagePaths as $path) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $this->dataUri->execute($path)]];
        }

        $payload = [
            'model' => $model ?? $config->vision_model,
            'messages' => [
                ['role' => 'system', 'content' => 'Rispondi solo con JSON conforme allo schema. Non inventare dati che non leggi nelle immagini: lascia vuoto o null.'],
                ['role' => 'user', 'content' => $content],
            ],
            'temperature' => 0,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => ['name' => $schemaName, 'strict' => true, 'schema' => $schema],
            ],
            'provider' => ['require_parameters' => true],
        ];

        $json = app(OpenAiCompatibleRequestAction::class)->execute($config->vision_base_url.'/chat/completions', $payload, $config->vision_timeout);

        $text = data_get($json, 'choices.0.message.content');
        if (! is_string($text) || trim($text) === '') {
            throw new AiRequestException('Risposta AI senza contenuto.');
        }

        try {
            $decoded = json_decode($this->stripFence($text), true);
        } catch (JsonException|PcreException $e) {
            throw new AiRequestException('Risposta AI non in formato JSON.', previous: $e);
        }
        if (! is_array($decoded)) {
            throw new AiRequestException('Risposta AI non in formato JSON.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /** Alcuni modelli racchiudono il JSON in ```json ... ``` anche con lo schema richiesto. */
    private function stripFence(string $text): string
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text);
        }

        return $text;
    }
}
