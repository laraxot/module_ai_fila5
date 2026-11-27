<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Prospecting;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Modules\AI\Tests\TestCase;

uses(TestCase::class);

test('offline HTML audit emits evidence and never touches the network', function (): void {
    Http::fake();
    $status = Artisan::call('ai:prospects:audit-html', [
        'source_url' => 'https://www.openstreetmap.org/node/11',
        'file' => __DIR__.'/../../Fixtures/prospect-offline.html',
        '--website' => 'https://example.org',
    ]);

    expect($status)->toBe(0);
    $result = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
    expect($result['network_requests'])->toBe(0)
        ->and($result['send_permitted'])->toBeFalse()
        ->and($result['ranking']['score'])->toBe(55)
        ->and(array_column($result['signals'], 'code'))->toContain('manual_booking_only', 'catalogue_link');
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');

test('offline audit rejects metadata URLs pointing to local services', function (): void {
    Http::fake();
    $status = Artisan::call('ai:prospects:audit-html', [
        'source_url' => 'http://127.0.0.1/admin',
        'file' => __DIR__.'/../../Fixtures/prospect-offline.html',
    ]);
    expect($status)->toBe(1);
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');
