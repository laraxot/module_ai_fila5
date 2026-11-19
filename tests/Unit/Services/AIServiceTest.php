<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Services;

use Modules\AI\Actions\CompletionAction;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

beforeEach(function (): void {
    /** @var TestCase $this */
<<<<<<< HEAD
    $this->action = new CompletionAction();
=======
    $this->action = new CompletionAction;
>>>>>>> laraxot/dev
});

describe('CompletionAction', function (): void {
    test('_action_can_be_instantiated', function (): void {
        /** @var TestCase $this */
        Assert::assertInstanceOf(CompletionAction::class, $this->action);
    });
});
