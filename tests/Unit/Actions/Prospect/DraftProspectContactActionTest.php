<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions\Prospect;

use Modules\AI\Actions\DraftProspectContactAction;
use Modules\AI\Enums\AiProspectDraftStatusEnum;
use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectDraft;
use Modules\AI\Models\AiProspectSignal;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

describe('DraftProspectContactAction', function (): void {
    test('creates draft when signal confidence >= 30', function (): void {
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => 'prospect_draft1',
            'workspace_id' => 'ws_test',
            'name' => 'Acme Srl',
            'domain' => 'acme.com',
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
            'confidence' => 60,
            'score' => 70,
            'breakdown' => [],
            'note' => null,
        ]);
        $signal->save();

        $action = new DraftProspectContactAction;
        $draft = $action->execute($prospect);

        Assert::assertInstanceOf(AiProspectDraft::class, $draft);
        Assert::assertSame(AiProspectDraftStatusEnum::DRAFT, $draft->status);
        Assert::assertNull($draft->sent_at, 'Draft must never be auto-sent');
        Assert::assertNotNull($draft->lawful_basis);
        Assert::assertSame('legitimate_interest', $draft->lawful_basis['basis']);
        Assert::assertSame($prospect->id, $draft->ai_prospect_id);
    });

    test('throws exception when signal confidence < 30', function (): void {
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => 'prospect_draft2',
            'workspace_id' => 'ws_test',
            'name' => 'Low Co',
            'domain' => 'low.com',
            'status' => AiProspectStatusEnum::NEW,
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
            'score' => 20,
            'breakdown' => [],
            'note' => null,
        ]);
        $signal->save();

        $action = new DraftProspectContactAction;
        expect(fn () => $action->execute($prospect))->toThrow(\Exception::class, 'Insufficient signal confidence');
    });
});