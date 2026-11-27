<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Prospecting;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Modules\AI\Tests\TestCase;

uses(TestCase::class);

/**
 * @see \Modules\AI\Console\Commands\AuditProspectHtmlCommand::handle() — l'output JSON è il contratto dell'audit offline
 */
test('offline HTML audit emits evidence and never touches the network', function (): void {
    Http::fake();
    $status = Artisan::call('ai:prospects:audit-html', [
        'source_url' => 'https://www.openstreetmap.org/node/11',
        'file' => __DIR__.'/../../Fixtures/prospect-offline.html',
        '--website' => 'https://example.org',
    ]);

    expect($status)->toBe(0);
    $result = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($result)) {
        throw new \UnexpectedValueException('The offline audit must emit a JSON object.');
    }
    $ranking = $result['ranking'] ?? null;
    $signals = $result['signals'] ?? null;
    if (! is_array($ranking) || ! is_array($signals)) {
        throw new \UnexpectedValueException('The offline audit JSON has an invalid evidence shape.');
    }
    expect($result['network_requests'])->toBe(0)
        ->and($result['send_permitted'])->toBeFalse()
        ->and($ranking['score'])->toBe(10)
        ->and(array_column($signals, 'code'))->toContain('has-booking');
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');

test('offline audit rejects metadata URLs pointing to local services', function (): void {
    Http::fake();
    $status = Artisan::call('ai:prospects:audit-html', [
        'source_url' => 'http://127.0.0.1/admin',
        'file' => __DIR__.'/../../Fixtures/prospect-offline.html',
    ]);
    expect($status)->toBe(0);
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');
