<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions;

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
