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
    config(['services.openrouter.key' => 'test-key', 'app.name' => 'Test']);
    FakeTenantAiConfig::bind(['vision_model' => 'vendor/vision-model', 'image_model' => 'vendor/image-model']);
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

it('fails clearly without an API key, without calling the provider', function (): void {
    config(['services.openrouter.key' => null]);
    Http::fake();

    expect(fn () => app(ExtractStructuredDataFromImagesAction::class)->execute([tinyPng()], 'x', 's', ['type' => 'object']))
        ->toThrow(AiRequestException::class, 'OPENROUTER_API_KEY');

    Http::assertNothingSent();
});

it('turns an HTTP error into an AiRequestException with the provider message', function (): void {
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
