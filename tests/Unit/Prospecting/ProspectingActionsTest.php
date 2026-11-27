<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Prospecting;

use Illuminate\Validation\ValidationException;
use Modules\AI\Actions\Prospecting\ExtractProspectSignalsAction;
use Modules\AI\Actions\Prospecting\ImportOsmCandidatesAction;
use Modules\AI\Actions\Prospecting\NormalizeProspectCandidateAction;
use Modules\AI\Actions\Prospecting\ScoreProspectAction;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

test('normalizes website domains and produces deterministic fingerprints', function (): void {
    $normalize = app(NormalizeProspectCandidateAction::class);
    $one = $normalize->execute('restaurant-demo', [
        'name' => ' Trattoria A ',
        'website' => 'https://www.example.org/menu',
        'source_url' => 'https://www.openstreetmap.org/node/123',
        'city' => 'Venezia',
        'country' => 'it',
    ]);
    $two = $normalize->execute('restaurant-demo', [
        'name' => 'Trattoria A nuova denominazione',
        'website' => 'https://example.org/',
        'source_url' => 'https://www.openstreetmap.org/node/124',
    ]);

    Assert::assertSame('example.org', $one['domain']);
    Assert::assertSame('IT', $one['country']);
    Assert::assertSame($one['fingerprint'], $two['fingerprint']);
    Assert::assertSame('restaurant-demo', $one['workspace']);
})->group('AI-PROSPECTING-INV-1');

test('requires a source and rejects non-public or non-http URL metadata', function (string $url): void {
    app(NormalizeProspectCandidateAction::class)->execute('demo', [
        'name' => 'Azienda',
        'source_url' => $url,
    ]);
})->with([
    'missing source' => [''],
    'localhost' => ['http://localhost/internal'],
    'private IP' => ['http://192.168.1.8/'],
    'metadata IP' => ['http://169.254.169.254/'],
    'non-HTTP' => ['file:///etc/passwd'],
    'local TLD' => ['http://intranet.local/'],
])->throws(ValidationException::class)->group('AI-PROSPECTING-INV-2');

test('does not invent missing booking or contact evidence', function (): void {
    $extract = app(ExtractProspectSignalsAction::class);
    $signals = $extract->execute('<html><body>Benvenuti, siamo aperti dal martedi alla domenica.</body></html>', 'https://example.org');
    $score = app(ScoreProspectAction::class)->execute('https://example.org', $signals);

    Assert::assertSame([], $signals);
    Assert::assertSame(10, $score['score']);
    Assert::assertCount(1, $score['reasons']);
})->group('AI-PROSPECTING-INV-3');

test('ranks only observed hints and never double counts a signal', function (): void {
    $html = '<p>Prenotazioni solo per telefono.</p><a href="/menu">Menu</a>';
    $signals = app(ExtractProspectSignalsAction::class)->execute($html, 'https://example.org');
    $score = app(ScoreProspectAction::class)->execute('https://example.org', [...$signals, ...$signals]);

    Assert::assertContains('manual_booking_only', array_column($signals, 'code'));
    Assert::assertContains('catalogue_link', array_column($signals, 'code'));
    Assert::assertSame(55, $score['score']);
    Assert::assertCount(3, $score['reasons']);
})->group('AI-PROSPECTING-INV-4');

test('translates an OSM export to companies without collecting email or phone', function (): void {
    $input = ['elements' => [
        ['type' => 'node', 'id' => 123, 'tags' => [
            'name' => 'Esempio', 'amenity' => 'restaurant',
            'website' => 'https://example.org', 'email' => 'person@example.org', 'phone' => '+3900000',
        ]],
        ['type' => 'way', 'id' => 456, 'tags' => ['amenity' => 'restaurant']],
    ]];

    $result = app(ImportOsmCandidatesAction::class)->execute($input);

    Assert::assertCount(1, $result);
    Assert::assertSame('https://www.openstreetmap.org/node/123', $result[0]['source_url']);
    Assert::assertArrayNotHasKey('email', $result[0]);
    Assert::assertArrayNotHasKey('phone', $result[0]);
})->group('AI-PROSPECTING-INV-5');
