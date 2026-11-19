<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Assistant;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\AI\Actions\Assistant\RequestAssistantCompletionAction;
use Modules\AI\Tests\Support\FakeTenantAiConfig;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

beforeEach(function (): void {
    FakeTenantAiConfig::bind([]);
    config(['services.groq.key' => 'gsk-test', 'services.openrouter.key' => 'sk-or-test', 'services.openai.api_key' => 'sk-openai-test']);
});

/** @return array{choices: list<array{message: array{content: string}}>} */
function chatReply(string $content): array
{
    return ['choices' => [['message' => ['content' => $content]]]];
}

test('_uses_groq_first_with_the_production_text_model_and_json_mode', function (): void {
    Http::fake(['api.groq.com/*' => Http::response(chatReply('{"answer":"Si"}'))]);

    $content = app(RequestAssistantCompletionAction::class)->execute('Reply in JSON', 'prompt');

    Assert::assertSame('{"answer":"Si"}', $content);
    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.groq.com/openai/v1/chat/completions')
        && $request['model'] === 'openai/gpt-oss-120b'
        && $request['response_format'] === ['type' => 'json_object']
        && data_get($request->data(), 'messages.0') === ['role' => 'system', 'content' => 'Reply in JSON']
        && $request->hasHeader('Authorization', 'Bearer gsk-test'));
});

test('_falls_back_to_openrouter_when_groq_fails', function (): void {
    Http::fake([
        'api.groq.com/*' => Http::response(['error' => ['message' => 'rate limit']], 429),
        'openrouter.ai/*' => Http::response(chatReply('{"answer":"Da OpenRouter"}')),
    ]);

    Assert::assertSame('{"answer":"Da OpenRouter"}', app(RequestAssistantCompletionAction::class)->execute('system', 'prompt'));
    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://openrouter.ai/api/v1/') && $request['model'] === 'openrouter/free');
});

test('_skips_providers_without_key_and_ends_on_openai', function (): void {
    config(['services.groq.key' => null, 'services.openrouter.key' => '']);
    Http::fake(['api.openai.com/*' => Http::response(chatReply('{"answer":"Da OpenAI"}'))]);

    Assert::assertSame('{"answer":"Da OpenAI"}', app(RequestAssistantCompletionAction::class)->execute('system', 'prompt'));
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'groq.com') || str_contains($request->url(), 'openrouter.ai'));
});

test('_adds_the_json_instruction_when_no_message_mentions_json', function (): void {
    // Groq e OpenAI rispondono 400 a json_object se nessun messaggio contiene «json» (visto dal vivo il 2026-11-17).
    Http::fake(['api.groq.com/*' => Http::response(chatReply('{"answer":"Si"}'))]);

    app(RequestAssistantCompletionAction::class)->execute('Rispondi in una frase.', 'Che cos e una segnalazione?');

    Http::assertSent(fn (Request $request): bool => data_get($request->data(), 'messages.0.content') === "Rispondi in una frase.\n\n".RequestAssistantCompletionAction::JSON_INSTRUCTION);
});
