<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\AI\Actions\ClassifyTicketAction;
use Modules\AI\Actions\Support\MakeAIRequestAction;
use Modules\AI\Actions\SuggestSolutionsAction;
use PHPUnit\Framework\Assert;

function fakeChatCompletion(string $content): void
{
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $content]]]])]);
}

describe('MakeAIRequestAction config', function (): void {
    test('_authenticates_with_services_openai_api_key_when_ai_key_is_missing', function (): void {
        config(['services.openai.api_key' => 'sk-from-services', 'ai.openai_api_key' => null]);
        fakeChatCompletion('{"ok":true}');

        $content = (new MakeAIRequestAction)->execute('prompt', 'classification');

        Assert::assertSame('{"ok":true}', $content);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer sk-from-services')
            && $request->url() === 'https://api.openai.com/v1/chat/completions');
    });

    test('_ai_keys_override_services_openai_keys', function (): void {
        config([
            'services.openai.api_key' => 'sk-from-services',
            'ai.openai_api_key' => 'sk-from-ai',
            'ai.openai_base_url' => 'https://llm.example.test/v1',
        ]);
        fakeChatCompletion('{}');

        (new MakeAIRequestAction)->execute('prompt', 'classification');

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer sk-from-ai')
            && $request->url() === 'https://llm.example.test/v1/chat/completions');
    });
});

describe('Ticket AI actions', function (): void {
    test('_classify_sends_the_classification_prompt_and_decodes_the_json_answer', function (): void {
        config(['cache.default' => 'array']);
        Cache::flush();
        fakeChatCompletion('{"category":"ambiente","confidence":0.9}');

        $result = app(ClassifyTicketAction::class)->execute('Rifiuti abbandonati', 'Via Roma 1');

        Assert::assertSame('ambiente', $result['category']);
        Http::assertSent(function (Request $request): bool {
            $prompt = $request['messages'][1]['content'];

            return str_contains($prompt, 'Classifica il seguente ticket')
                && str_contains($prompt, 'Titolo: Rifiuti abbandonati')
                && str_contains($prompt, 'Descrizione: Via Roma 1');
        });
    });

    test('_suggest_sends_the_solutions_prompt_with_the_category', function (): void {
        config(['cache.default' => 'array']);
        Cache::flush();
        fakeChatCompletion('{"solutions":[]}');

        $result = app(SuggestSolutionsAction::class)->execute('Buca', 'Via Po', 'infrastruttura');

        Assert::assertSame([], $result['solutions']);
        Http::assertSent(fn (Request $request): bool => str_contains($request['messages'][1]['content'], 'Suggerisci soluzioni per questo ticket di infrastruttura')
            && str_contains($request['messages'][1]['content'], 'Titolo: Buca'));
    });
});
