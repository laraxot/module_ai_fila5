<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions;

use Modules\AI\Actions\CancelAiActionProposalAction;
use Modules\AI\Enums\AiActionProposalStatusEnum;
use Modules\AI\Models\AiActionProposal;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;
use RuntimeException;

uses(TestCase::class);

/*
 * #42 — Unit state-machine tests only. This fake saving hook deliberately
 * avoids changing real databases; cross-process CAS still requires integration tests.
 */
beforeEach(function (): void {
    AiActionProposal::saving(static fn (): bool => false);
});

test('cancels one pending proposal and rejects a repeated cancellation', function (): void {
    $proposal = new AiActionProposal;
    $proposal->forceFill([
        'type' => 'noop',
        'payload' => [],
        'status' => AiActionProposalStatusEnum::PENDING,
    ]);

    $action = new CancelAiActionProposalAction;
    $action->execute($proposal);

    Assert::assertSame(AiActionProposalStatusEnum::CANCELLED, $proposal->status);
    expect(fn () => $action->execute($proposal))->toThrow(RuntimeException::class, 'only PENDING');
})->group('AI-PROPOSAL-INV-42');

test('never cancels confirmed, executed, failed or already cancelled proposals', function (): void {
    $action = new CancelAiActionProposalAction;

    foreach ([
        AiActionProposalStatusEnum::CONFIRMED,
        AiActionProposalStatusEnum::EXECUTED,
        AiActionProposalStatusEnum::FAILED,
        AiActionProposalStatusEnum::CANCELLED,
    ] as $status) {
        $proposal = new AiActionProposal;
        $proposal->forceFill([
            'type' => 'noop',
            'payload' => [],
            'status' => $status,
        ]);

        expect(fn () => $action->execute($proposal))->toThrow(RuntimeException::class, 'only PENDING');
        Assert::assertSame($status, $proposal->status);
    }
})->group('AI-PROPOSAL-INV-42');
