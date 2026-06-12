<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions;

use Illuminate\Support\Facades\Http;
use Modules\AI\Actions\GeneratePredictionsAction;
use Modules\AI\Datas\PredictionData;
use Modules\AI\Tests\Support\OpenAiHttpFake;
use Modules\AI\Tests\TestCase;

uses(TestCase::class);

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
    expect($prediction->category)->toBe('Politica');
});

it('generates valid prediction data structure', function () {
    OpenAiHttpFake::fakeCompletions(OpenAiHttpFake::predictionPayload());

    $prediction = app(GeneratePredictionsAction::class)->execute('Test prediction');

    expect($prediction)->toHaveProperties([
        'title', 'description', 'content', 'excerpt', 'category', 'tags',
        'closed_at', 'ends_at', 'liquidity_parameter', 'stocks_count', 'is_wagerable',
    ]);
})->skip('Run with full OpenAiHttpFake suite when needed');
