<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions\Prospect;

use Modules\AI\Actions\ImportProspectsAction;
use Modules\AI\Models\AiProspect;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

describe('Import Prospects Action', function (): void {
    test('imports rows with deterministic public_id and skips suppressed', function (): void {
        $action = new ImportProspectsAction;

        $rows = [
            [
                'domain' => 'acme.com',
                'name' => 'Acme Srl',
                'industry' => 'Manufacturing',
                'city' => 'Milano',
                'country_code' => 'IT',
            ],
            [
                'domain' => 'acme.com',
                'name' => 'Acme Srl Duplicate',
                'industry' => 'Manufacturing',
            ],
        ];

        $imported = $action->execute($rows, 'workspace_1', 'csv', 'https://source.csv');

        // 1 row: second acme.com is duplicate (same public_id)
        Assert::assertSame(1, $imported);

        // Existing prospect has deterministic id
        $prospect = AiProspect::query()->where('workspace_id', 'workspace_1')->first();
        Assert::assertNotNull($prospect);
        Assert::assertEquals(hash('sha256', 'acme.com'), $prospect->public_id);
        Assert::assertEquals('acme.com', $prospect->domain);
        Assert::assertEquals('workspace_1', $prospect->workspace_id);
        Assert::assertEquals('csv', $prospect->source);
    });

    test('skips suppressed prospects even on re-import', function (): void {
        // Create a prospect then suppress it
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => hash('sha256', 'blocked.com'),
            'workspace_id' => 'workspace_1',
            'name' => 'Blocked Co',
            'domain' => 'blocked.com',
            'status' => 'new',
        ]);
        $prospect->save();

        \Modules\AI\Models\AiSuppression::query()->create([
            'public_id' => hash('sha256', 'blocked.com'),
            'workspace_id' => 'workspace_1',
            'email_hash' => hash('sha256', 'info@blocked.com'),
            'reason' => 'do_not_contact',
            'suppressed_at' => now(),
        ]);

        $action = new ImportProspectsAction;
        $imported = $action->execute([
            [
                'domain' => 'blocked.com',
                'name' => 'Blocked Co',
                'industry' => 'Manufacturing',
            ],
        ], 'workspace_1', 'csv', 'https://source.csv');

        // Suppressed prospect is not re-imported
        Assert::assertSame(0, $imported);
    });

    test('empty domain rows are skipped', function (): void {
        $action = new ImportProspectsAction;

        $imported = $action->execute([
            ['name' => 'No Domain'],
            ['domain' => '', 'name' => 'Empty Domain'],
        ], 'workspace_1', 'csv', null);

        Assert::assertSame(0, $imported);
    });
});
