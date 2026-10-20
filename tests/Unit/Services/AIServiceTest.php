<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\AIService;
use PHPUnit\Framework\Assert;

// Il modello risponde con una stringa JSON: AIService la decodifica nell'array del flusso
// o, se non e' JSON, restituisce il default del flusso (prima lanciava sempre InvalidArgumentException).

function fakeAiServiceCompletion(string $content): void
{
    config(['cache.default' => 'array']);
    Cache::flush();
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $content]]]])]);
}

describe('AIService JSON flows', function (): void {
    test('_classify_ticket_decodes_the_json_answer_of_the_model', function (): void {
        fakeAiServiceCompletion('{"category":"ambiente","confidence":0.9}');

        $result = (new AIService)->classifyTicket('Rifiuti abbandonati', 'Via Roma 1');

        Assert::assertSame('ambiente', $result['category']);
        Assert::assertSame(0.9, $result['confidence']);
    });

    test('_optimize_routing_decodes_the_json_answer_of_the_model', function (): void {
        fakeAiServiceCompletion('{"assignments":[{"ticket_id":1,"agent_id":2}],"efficiency_score":0.8}');

        $result = (new AIService)->optimizeRouting([['id' => 1]], [['id' => 2]]);

        Assert::assertSame([['ticket_id' => 1, 'agent_id' => 2]], $result['assignments']);
    });

    test('_analyze_sentiment_falls_back_to_neutral_when_the_answer_is_not_json', function (): void {
        fakeAiServiceCompletion('non sono JSON');

        $result = (new AIService)->analyzeSentiment('Va tutto bene');

        Assert::assertSame('neutral', $result['sentiment']);
        Assert::assertSame(0.5, $result['confidence']);
    });
});
