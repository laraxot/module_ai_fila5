<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions;

use Illuminate\Support\Facades\Http;
use Modules\AI\Actions\GeneratePredictionsAction;
use Modules\AI\Datas\PredictionData;
use Modules\AI\Tests\Support\OpenAiHttpFake;
use Modules\AI\Tests\TestCase;

uses(TestCase::class);

/**
 * Test per la generazione AI di predizioni singole.
 */
it('generates a single prediction with AI', function () {
    OpenAiHttpFake::fakeCompletions(OpenAiHttpFake::predictionPayload([
        'title' => 'Elezioni politiche 2026',
        'category' => 'Politica',
    ]));

    $prediction = app(GeneratePredictionsAction::class)->execute('Elezioni politiche 2026', [
        'category' => 'Politica',
        'language' => 'it',
    ]);

    expect($prediction)->toBeInstanceOf(PredictionData::class);
    expect($prediction->title)->toBe('Elezioni politiche 2026');
    expect($prediction->description)->toBeString();
    expect($prediction->category)->toBe('Politica');
    expect($prediction->tags)->toBeArray();
    expect($prediction->closed_at)->toBeString();
    expect($prediction->is_wagerable)->toBeTrue();
});

/**
 * Test per la generazione di multiple predizioni.
 */
it('generates multiple unique predictions', function () {
    Http::fake([
        'api.openai.com/*' => Http::sequence()
            ->push(['choices' => [['text' => json_encode(OpenAiHttpFake::predictionPayload(['title' => 'Topic 0 title']), JSON_THROW_ON_ERROR)]], 'usage' => ['total_tokens' => 50]])
            ->push(['choices' => [['text' => json_encode(OpenAiHttpFake::predictionPayload(['title' => 'Topic 1 title']), JSON_THROW_ON_ERROR)]], 'usage' => ['total_tokens' => 50]])
            ->push(['choices' => [['text' => json_encode(OpenAiHttpFake::predictionPayload(['title' => 'Topic 2 title']), JSON_THROW_ON_ERROR)]], 'usage' => ['total_tokens' => 50]])
            ->push(['choices' => [['text' => json_encode(OpenAiHttpFake::predictionPayload(['title' => 'Topic 3 title']), JSON_THROW_ON_ERROR)]], 'usage' => ['total_tokens' => 50]])
            ->push(['choices' => [['text' => json_encode(OpenAiHttpFake::predictionPayload(['title' => 'Topic 4 title']), JSON_THROW_ON_ERROR)]], 'usage' => ['total_tokens' => 50]]),
    ]);

    $action = app(GeneratePredictionsAction::class);
    $titles = [];

    for ($i = 0; $i < 5; $i++) {
        $prediction = $action->execute("Topic {$i}");
        $titles[] = $prediction->title;
    }

    expect($titles)->toHaveCount(5);
    expect(array_unique($titles))->toHaveCount(5);
});

/**
 * Test per la validazione dei dati generati.
 */
it('generates valid prediction data structure', function () {
    OpenAiHttpFake::fakeCompletions(OpenAiHttpFake::predictionPayload());

    $prediction = app(GeneratePredictionsAction::class)->execute('Test prediction');

    expect($prediction)->toHaveProperties([
        'title',
        'description',
        'content',
        'excerpt',
        'category',
        'tags',
        'closed_at',
        'ends_at',
        'liquidity_parameter',
        'stocks_count',
        'is_wagerable',
    ]);

    expect($prediction->liquidity_parameter)->toBeFloat();
    expect($prediction->liquidity_parameter)->toBeGreaterThanOrEqual(0.0);
    expect($prediction->liquidity_parameter)->toBeLessThanOrEqual(1.0);

    expect($prediction->stocks_count)->toBeInt();
    expect($prediction->stocks_count)->toBeGreaterThan(0);

    expect($prediction->tags)->toBeArray();
    expect($prediction->tags)->not->toBeEmpty();
});

/**
 * Test per la validazione delle date future.
 */
it('generates future dates for closed_at', function () {
    OpenAiHttpFake::fakeCompletions(OpenAiHttpFake::predictionPayload());

    $now = now();
    $prediction = app(GeneratePredictionsAction::class)->execute('Test prediction');

    $closedAt = new DateTime($prediction->closed_at);
    expect($closedAt)->toBeGreaterThan($now);
});

/**
 * Test per il fallback quando OpenAI non è disponibile.
 */
it('uses fallback when OpenAI API key is not configured', function () {
    config(['services.openai.api_key' => null]);

    expect(fn () => app(GeneratePredictionsAction::class)->execute('Test prediction'))
        ->toThrow(RuntimeException::class);
})->skip('GeneratePredictionsAction has no offline fallback yet');

/**
 * Test per la generazione con categoria specifica.
 */
it('respects category parameter', function () {
    OpenAiHttpFake::fakeCompletions(OpenAiHttpFake::predictionPayload([
        'title' => 'Serie A 2026',
        'category' => 'Sport',
    ]));

    $prediction = app(GeneratePredictionsAction::class)->execute('Serie A 2026', [
        'category' => 'Sport',
    ]);

    expect($prediction->category)->toBe('Sport');
});

/**
 * Test per la conversione in array per Predict model.
 */
it('converts prediction data to predict array', function () {
    OpenAiHttpFake::fakeCompletions(OpenAiHttpFake::predictionPayload());

    $prediction = app(GeneratePredictionsAction::class)->execute('Test prediction');
    $predictArray = $prediction->toPredictArray();

    expect($predictArray)->toBeArray();
    expect($predictArray)->toHaveKeys([
        'title',
        'description',
        'content',
        'excerpt',
        'category_name',
        'tags',
        'closed_at',
        'ends_at',
        'liquidity_parameter',
        'stocks_count',
        'is_wagerable',
        'status',
        'published_at',
    ]);

    expect($predictArray['title'])->toBeArray();
    expect($predictArray['title'])->toHaveKey('it');
});

/**
 * Test per la validazione dei tags.
 */
it('generates valid tags array', function () {
    OpenAiHttpFake::fakeCompletions(OpenAiHttpFake::predictionPayload([
        'tags' => ['bitcoin', 'crypto', 'mercato'],
    ]));

    $prediction = app(GeneratePredictionsAction::class)->execute('Bitcoin crypto prediction');

    expect($prediction->tags)->toBeArray();
    expect($prediction->tags)->not->toBeEmpty();

    foreach ($prediction->tags as $tag) {
        expect($tag)->toBeString();
        expect($tag)->not->toBeEmpty();
    }
});

/**
 * Test per la gestione errori con topic vuoto.
 */
it('handles empty topic gracefully', function () {
    OpenAiHttpFake::fakeCompletions(OpenAiHttpFake::predictionPayload());

    expect(fn () => app(GeneratePredictionsAction::class)->execute(''))
        ->not->toThrow(Exception::class);
})->todo('Implement error handling');

/**
 * Test per la generazione batch con rate limiting.
 */
it('respects rate limiting in batch generation', function () {
    Http::fake([
        'api.openai.com/*' => Http::sequence()
            ->push(['choices' => [['text' => json_encode(OpenAiHttpFake::predictionPayload(['title' => 'Prediction 0']), JSON_THROW_ON_ERROR)]], 'usage' => ['total_tokens' => 50]])
            ->push(['choices' => [['text' => json_encode(OpenAiHttpFake::predictionPayload(['title' => 'Prediction 1']), JSON_THROW_ON_ERROR)]], 'usage' => ['total_tokens' => 50]])
            ->push(['choices' => [['text' => json_encode(OpenAiHttpFake::predictionPayload(['title' => 'Prediction 2']), JSON_THROW_ON_ERROR)]], 'usage' => ['total_tokens' => 50]]),
    ]);

    $action = app(GeneratePredictionsAction::class);
    $startTime = microtime(true);

    for ($i = 0; $i < 3; $i++) {
        $action->execute("Prediction {$i}");
    }

    $elapsedTime = microtime(true) - $startTime;
    expect($elapsedTime)->toBeGreaterThan(1.5);
})->skip('Rate limiting not implemented yet');
