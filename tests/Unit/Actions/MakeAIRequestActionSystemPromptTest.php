<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\AI\Actions\MakeAIRequestAction;
use Modules\AI\Tests\TestCase;

uses(TestCase::class);

test('_uses_the_historic_system_prompt_by_default_and_the_given_one_when_passed', function (): void {
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{}']]]])]);

    (new MakeAIRequestAction)->execute('prompt', 'classification');
    (new MakeAIRequestAction)->execute('prompt', 'assistant', [], 'Custom system prompt');

    Http::assertSent(fn (Request $request): bool => $request['messages'][0]['content'] === MakeAIRequestAction::DEFAULT_SYSTEM_PROMPT);
    Http::assertSent(fn (Request $request): bool => $request['messages'][0]['content'] === 'Custom system prompt');
});
