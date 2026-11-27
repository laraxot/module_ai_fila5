<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions\Prospect;

use Modules\AI\Actions\SuppressProspectAction;
use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\AI\Enums\AiSuppressionReasonEnum;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiSuppression;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

describe('SuppressProspectAction', function (): void {
    test('creates suppression record and sets prospect status to cold_lost enum value', function (): void {
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => 'prospect_supp1',
            'workspace_id' => 'ws_test',
            'name' => 'Blocked Co',
            'domain' => 'blocked.com',
        ]);
        $prospect->save();

        $action = new SuppressProspectAction();
        $suppression = $action->execute('ws_test', 'prospect_supp1', AiSuppressionReasonEnum::DO_NOT_CONTACT);

        $prospect->refresh();

        Assert::assertInstanceOf(AiSuppression::class, $suppression);
        Assert::assertSame(AiProspectStatusEnum::COLD_LOST, $prospect->status);
    });

    test('uses AiProspectStatusEnum::COLD_LOST->value instead of raw string', function (): void {
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => 'prospect_supp2',
            'workspace_id' => 'ws_test',
            'name' => 'Blocked Co 2',
            'domain' => 'blocked.com',
        ]);
        $prospect->save();

        $action = new SuppressProspectAction();
        $action->execute('ws_test', 'prospect_supp2', AiSuppressionReasonEnum::DO_NOT_CONTACT);

        $prospect->refresh();

        Assert::assertSame(AiProspectStatusEnum::COLD_LOST, $prospect->status);
    });

    test('isSuppressed checks exist only by public_id+workspace', function (): void {
        AiSuppression::query()->create([
            'public_id' => 'suppression_lookup',
            'workspace_id' => 'ws_lookup',
            'reason' => 'do_not_contact',
            'suppressed_at' => now(),
        ]);

        Assert::assertNotNull(AiSuppression::findForPublicId('suppression_lookup', 'ws_lookup'));
        Assert::assertNull(AiSuppression::findForPublicId('suppression_lookup', 'ws_other'));
        Assert::assertNull(AiSuppression::findForPublicId('suppression_missing', 'ws_lookup'));
    });
});