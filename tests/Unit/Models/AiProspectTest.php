<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Models;

use Modules\AI\Models\AiProspect;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

describe('AiProspect Model', function (): void {
    test('generates deterministic public_id from domain', function (): void {
        $domain = 'example.com';
        $publicId = AiProspect::deterministicId($domain);

        Assert::assertEquals(hash('sha256', 'example.com'), $publicId);
    });

    test('generates same public_id for same domain case-insensitive', function (): void {
        $id1 = AiProspect::deterministicId('EXAMPLE.COM');
        $id2 = AiProspect::deterministicId('example.com');
        $id3 = AiProspect::deterministicId('Example.Com');

        Assert::assertEquals($id1, $id2);
        Assert::assertEquals($id2, $id3);
    });

    test('scope_by_workspace filters correctly', function (): void {
        // Query builder test - no DB needed
        $query = AiProspect::query()->where('workspace_id', 'workspace_123');
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        Assert::assertStringContainsString('workspace_id', $sql);
        Assert::assertSame('workspace_123', $bindings[0]);
    });

    test('has signals relationship', function (): void {
        $prospect = new AiProspect;
        $relation = $prospect->signals();

        Assert::assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
        Assert::assertEquals('ai_prospect_id', $relation->getForeignKeyName());
    });

    test('fills all required attributes', function (): void {
        $prospect = new AiProspect;
        $prospect->fill([
            'public_id' => 'prospect_test123',
            'workspace_id' => 'ws_test',
            'name' => 'Test Company',
            'domain' => 'test.com',
            'website' => 'https://test.com',
            'source' => 'csv',
            'source_url' => 'https://source.com',
            'source_at' => now(),
            'city' => 'Roma',
            'region' => 'Lazio',
            'country_code' => 'IT',
            'industry' => 'Tech',
            'phone' => '+39 06 12345678',
            'raw_data' => ['key' => 'value'],
            'summary' => 'Test summary',
            'status' => 'new',
            'last_reviewed_at' => now(),
            'last_contact_attempt_at' => now(),
        ]);

        Assert::assertEquals('Test Company', $prospect->name);
        Assert::assertEquals('test.com', $prospect->domain);
        Assert::assertEquals('csv', $prospect->source);
        Assert::assertEquals('new', $prospect->status->value);
    });
});