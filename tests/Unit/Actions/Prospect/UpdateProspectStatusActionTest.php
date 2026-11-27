<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions\Prospect;

use Modules\AI\Actions\UpdateProspectStatusAction;
use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectSignal;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

describe('UpdateProspectStatusAction', function (): void {
    test('NEW transitions to QUALIFIED when confidence >= minConfidence', function (): void {
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => 'prospect_test1',
            'workspace_id' => 'ws_test',
            'name' => 'Test Co',
            'domain' => 'test1.com',
            'status' => AiProspectStatusEnum::NEW,
        ]);
        $prospect->save();

        $signal = new AiProspectSignal;
        $signal->fill([
            'ai_prospect_id' => $prospect->id,
            'source' => 'csv',
            'source_url' => '',
            'source_at' => now(),
            'quality_source' => 'high',
            'confidence' => 80,
            'score' => 70,
            'breakdown' => [],
            'note' => null,
        ]);
        $signal->save();

        $action = new UpdateProspectStatusAction;
        $action->execute($prospect, 30, 50);

        $prospect->refresh();
        Assert::assertSame(AiProspectStatusEnum::QUALIFIED, $prospect->status);
    });

    test('QUALIFIED stays QUALIFIED - no automatic CONTACTED', function (): void {
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => 'prospect_test2',
            'workspace_id' => 'ws_test',
            'name' => 'Test Co',
            'domain' => 'test2.com',
            'status' => AiProspectStatusEnum::QUALIFIED,
        ]);
        $prospect->save();

        $signal = new AiProspectSignal;
        $signal->fill([
            'ai_prospect_id' => $prospect->id,
            'source' => 'csv',
            'source_url' => '',
            'source_at' => now(),
            'quality_source' => 'high',
            'confidence' => 80,
            'score' => 90,
            'breakdown' => [],
            'note' => null,
        ]);
        $signal->save();

        $action = new UpdateProspectStatusAction;
        $action->execute($prospect, 30, 50);

        $prospect->refresh();
        Assert::assertSame(AiProspectStatusEnum::QUALIFIED, $prospect->status);
    });

    test('QUALIFIED stays QUALIFIED - no automatic COLD_LOST', function (): void {
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => 'prospect_test3',
            'workspace_id' => 'ws_test',
            'name' => 'Test Co',
            'domain' => 'test3.com',
            'status' => AiProspectStatusEnum::QUALIFIED,
        ]);
        $prospect->save();

        $signal = new AiProspectSignal;
        $signal->fill([
            'ai_prospect_id' => $prospect->id,
            'source' => 'csv',
            'source_url' => '',
            'source_at' => now(),
            'quality_source' => 'low',
            'confidence' => 10,
            'score' => 10,
            'breakdown' => [],
            'note' => null,
        ]);
        $signal->save();

        $action = new UpdateProspectStatusAction;
        $action->execute($prospect, 30, 50);

        $prospect->refresh();
        Assert::assertSame(AiProspectStatusEnum::QUALIFIED, $prospect->status);
    });

    test('no signal -> no status change', function (): void {
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => 'prospect_test4',
            'workspace_id' => 'ws_test',
            'name' => 'Test Co',
            'domain' => 'test4.com',
            'status' => AiProspectStatusEnum::NEW,
        ]);
        $prospect->save();

        $action = new UpdateProspectStatusAction;
        $action->execute($prospect);

        $prospect->refresh();
        Assert::assertSame(AiProspectStatusEnum::NEW, $prospect->status);
    });
});