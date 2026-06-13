<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Services;

use Modules\AI\Services\AIService;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(\Modules\AI\Tests\TestCase::class);

beforeEach(function (): void {
    /** @var \Modules\AI\Tests\TestCase $this */
$this->service = new AIService;
});

describe('AIService', function (): void {
    test('_service_can_be_instantiated', function (): void {
        /** @var \Modules\AI\Tests\TestCase $this */
Assert::assertInstanceOf(AIService::class, $this->service);
    });
});
