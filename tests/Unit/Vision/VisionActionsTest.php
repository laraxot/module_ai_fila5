<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\AI\Actions\Vision\EditImageAction;
use Modules\AI\Actions\Vision\ExtractStructuredDataFromImagesAction;
use Modules\AI\Exceptions\AiRequestException;
use Modules\AI\Tests\Support\FakeTenantAiConfig;
use Modules\AI\Tests\TestCase;

use function Safe\base64_decode;
use function Safe\file_put_contents;
use function Safe\tempnam;

uses(TestCase::class);

/** PNG 1x1 valido, scritto in un file temporaneo. */
function tinyPng(): string
{
    $path = tempnam(sys_get_temp_dir(), 'ai-img').'.png';
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

    return $path;
}

beforeEach(function (): void {
    config(['services.openrouter.key' => 'test-key', 'services.groq.key' => 'groq-key', 'app.name' => 'Test']);
    FakeTenantAiConfig::bind([
        'vision_providers' => ['openrouter', 'groq'],
        'vision_models' => ['openrouter' => 'vendor/vision-model', 'groq' => 'qwen/qwen3.8-27b'],
        'image_model' => 'vendor/image-model',
        'free_models_only' => false,
    ]);
});

it('sends text first, images as data URIs and a strict json schema, and returns the decoded object', function (): void {
    Http::fake(['openrouter.ai/api/v1/chat/completions' => Http::response([
        'choices' => [['message' => ['content' => '```json'."\n".'{"items":[{"name":"Tiramisù","price":6.5}]}'."\n".'```']]],
    ])]);

    $result = app(ExtractStructuredDataFromImagesAction::class)->execute(
        [tinyPng()],
        'Leggi le voci.',
        'items',
        ['type' => 'object', 'properties' => ['items' => ['type' => 'array']], 'required' => ['items'], 'additionalProperties' => false],
    );

    expect($result)->toBe(['items' => [['name' => 'Tiramisù', 'price' => 6.5]]]);

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        $messages = $data['messages'] ?? null;
        $secondMessage = is_array($messages) ? ($messages[1] ?? null) : null;
        $secondMessage = is_array($secondMessage) ? $secondMessage : [];
        $contentParts = $secondMessage['content'] ?? null;
        $contentParts = is_array($contentParts) ? array_values($contentParts) : [];

        $textPart = $contentParts[0] ?? null;
        $imagePart = $contentParts[1] ?? null;
        $imageUrl = null;
        if (is_array($imagePart) && is_array($imagePart['image_url'] ?? null)) {
            $imageUrl = $imagePart['image_url']['url'] ?? null;
        }

        $responseFormat = $data['response_format'] ?? null;
        $responseFormat = is_array($responseFormat) ? $responseFormat : [];
        $jsonSchema = $responseFormat['json_schema'] ?? null;
        $jsonSchema = is_array($jsonSchema) ? $jsonSchema : [];
        $provider = $data['provider'] ?? null;
        $provider = is_array($provider) ? $provider : [];

        return $request->hasHeader('Authorization', 'Bearer test-key')
            && ($data['model'] ?? null) === 'vendor/vision-model'
            && $textPart === ['type' => 'text', 'text' => 'Leggi le voci.']
            && is_string($imageUrl)
            && str_starts_with($imageUrl, 'data:image/png;base64,')
            && ($jsonSchema['strict'] ?? null) === true
            && ($provider['require_parameters'] ?? null) === true;
    });
});

it('fails clearly without any API key, without calling the providers', function (): void {
    config(['services.openrouter.key' => null, 'services.groq.key' => '']);
    Http::fake();

    expect(fn () => app(ExtractStructuredDataFromImagesAction::class)->execute([tinyPng()], 'x', 's', ['type' => 'object']))
        ->toThrow(AiRequestException::class, 'OPENROUTER_API_KEY');

    Http::assertNothingSent();
});

it('turns an HTTP error into an AiRequestException with the provider message', function (): void {
    config(['services.groq.key' => null]);
    Http::fake(['*' => Http::response(['error' => ['message' => 'No endpoints found']], 404)]);

    expect(fn () => app(ExtractStructuredDataFromImagesAction::class)->execute([tinyPng()], 'x', 's', ['type' => 'object']))
        ->toThrow(AiRequestException::class, 'No endpoints found');
});

it('rejects non-image files before any request', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'ai-txt');
    file_put_contents($path, 'solo testo');
    Http::fake();

    expect(fn () => app(ExtractStructuredDataFromImagesAction::class)->execute([$path], 'x', 's', ['type' => 'object']))
        ->toThrow(AiRequestException::class, 'non supportato');

    Http::assertNothingSent();
});

it('edits an image through the images endpoint and returns the decoded bytes', function (): void {
    Http::fake(['openrouter.ai/api/v1/images' => Http::response(['data' => [['b64_json' => base64_encode('PNGDATA'), 'media_type' => 'image/png']]])]);

    $result = app(EditImageAction::class)->execute(tinyPng(), 'Pulisci il logo.');

    expect($result)->toBe(['mime' => 'image/png', 'bytes' => 'PNGDATA']);
    Http::assertSent(function (Request $request): bool {
        $data = $request->data();
        $references = $data['input_references'] ?? null;
        $references = is_array($references) ? array_values($references) : [];
        $firstReference = $references[0] ?? null;

        return ($data['model'] ?? null) === 'vendor/image-model'
            && is_string($firstReference)
            && str_starts_with($firstReference, 'data:image/png;base64,')
            && ($data['prompt'] ?? null) === 'Pulisci il logo.';
    });
});

it('falls back to Groq when OpenRouter fails, without the OpenRouter-only provider field', function (): void {
    Http::fake([
        'openrouter.ai/*' => Http::response(['error' => ['message' => 'Rate limit']], 429),
        'api.groq.com/openai/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => '{"ok":true}']]]]),
    ]);

    $result = app(ExtractStructuredDataFromImagesAction::class)->execute([tinyPng()], 'x', 's', ['type' => 'object']);

    expect($result)->toBe(['ok' => true]);
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.groq.com')
        && $request->hasHeader('Authorization', 'Bearer groq-key')
        && $request['model'] === 'qwen/qwen3.8-27b'
        && ! isset($request['provider'])
        && ! $request->hasHeader('X-Title'));
});

it('skips Groq when there are more images than it accepts and reports every reason', function (): void {
    Http::fake(['openrouter.ai/*' => Http::response(['error' => ['message' => 'Overloaded']], 503)]);
    $images = [tinyPng(), tinyPng(), tinyPng(), tinyPng()];

    expect(fn () => app(ExtractStructuredDataFromImagesAction::class)->execute($images, 'x', 's', ['type' => 'object']))
        ->toThrow(AiRequestException::class, 'groq: massimo 3 immagini');

    Http::assertSentCount(1);
});

it('marks the failure as rate limited when a provider answered 429', function (): void {
    config(['services.groq.key' => null]);
    Http::fake(['*' => Http::response(['error' => ['message' => 'Rate limit reached']], 429)]);

    try {
        app(ExtractStructuredDataFromImagesAction::class)->execute([tinyPng()], 'x', 's', ['type' => 'object']);
        $this->fail('nessuna eccezione');
    } catch (AiRequestException $e) {
        expect($e->isRateLimited())->toBeTrue();
    }
});

it('with free models only, sends OpenRouter only free models, as a fallback list', function (): void {
    FakeTenantAiConfig::bind([
        'vision_providers' => ['openrouter'],
        'vision_models' => ['openrouter' => ['vendor/paid-model', 'nvidia/nemotron-3.5-super-vl:free', 'openrouter/free']],
        'free_models_only' => true,
    ]);
    Http::fake(['openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => '{"ok":true}']]]])]);

    app(ExtractStructuredDataFromImagesAction::class)->execute([tinyPng()], 'x', 's', ['type' => 'object']);

    Http::assertSent(fn (Request $request): bool => $request['model'] === 'nvidia/nemotron-3.5-super-vl:free'
        && $request['models'] === ['nvidia/nemotron-3.5-super-vl:free', 'openrouter/free']);
});

it('with free models only, never calls a paid model and refuses image generation', function (): void {
    FakeTenantAiConfig::bind([
        'vision_providers' => ['openrouter'],
        'vision_models' => ['openrouter' => 'vendor/paid-model'],
        'image_model' => 'vendor/paid-image',
        'free_models_only' => true,
    ]);
    Http::fake();

    expect(fn () => app(ExtractStructuredDataFromImagesAction::class)->execute([tinyPng()], 'x', 's', ['type' => 'object']))
        ->toThrow(AiRequestException::class, 'nessun modello utilizzabile')
        ->and(fn () => app(EditImageAction::class)->execute(tinyPng(), 'x'))
        ->toThrow(AiRequestException::class, 'Nessun modello gratuito');
    Http::assertNothingSent();
});

it('defaults to free models: Groq first, then OpenRouter free models', function (): void {
    FakeTenantAiConfig::bind([]);
    $config = \Modules\AI\Datas\AIConfigData::make();

    expect($config->free_models_only)->toBeTrue()
        ->and($config->vision_providers)->toBe(['groq', 'openrouter']);
    foreach (explode(',', $config->vision_models['openrouter']) as $model) {
        expect(\Modules\AI\Enums\AiProviderEnum::OpenRouter->isFreeModel($model))->toBeTrue();
    }
});
