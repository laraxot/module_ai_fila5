<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Prospecting;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\AI\Actions\Prospecting\DiscoverProspectsInAreaAction;
use Modules\AI\Actions\Prospecting\FetchPublicPageAction;
use Modules\AI\Actions\Prospecting\GeocodeAreaAction;
use Modules\AI\Actions\Prospecting\OsmRequestAction;
use Modules\AI\Actions\Prospecting\ResearchProspectAction;
use Modules\AI\Actions\Prospecting\ResearchProspectWebsiteAction;
use Modules\AI\Exceptions\AiRequestException;
use Modules\AI\Models\AiProspect;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

beforeEach(function (): void {
    // Never run these migration tests on a real database.
    $connection = (new AiProspect)->getConnectionName();
    Assert::assertSame('sqlite', DB::connection($connection)->getDriverName());
    if (! Schema::connection($connection)->hasTable('ai_prospects')) {
        foreach (['2026_11_27_000001_create_ai_prospects_table', '2026_11_27_000002_create_ai_prospect_suppressions_table'] as $migration) {
            $this->artisan('migrate', ['--database' => $connection, '--path' => 'Modules/AI/database/migrations/'.$migration.'.php']);
        }
    }
    Cache::flush();
    config([
        'ai.prospecting.enabled' => true,
        'ai.prospecting.public_discovery_enabled' => true,
        'ai.prospecting.site_fetch_enabled' => true,
        'ai.prospecting.user_agent' => 'TestProspecting/1.0 (+https://example.test)',
    ]);
});

test('website HTTP is always disabled, even when an obsolete experimental flag is true', function (): void {
    Http::fake();
    $fetch = app(FetchPublicPageAction::class);
    foreach (['https://public.test', 'https://redirect.test', 'http://127.0.0.1', 'http://[::1]/', 'http://169.254.169.254'] as $url) {
        $result = $fetch->execute($url);
        Assert::assertFalse($result['ok']);
        Assert::assertSame(0, $result['status']);
        Assert::assertSame('', $result['html']);
        Assert::assertSame('remote_fetch_disabled_pending_ssrf_safe_adapter', $result['error']);
    }
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');

test('OSM discovery defaults to dry-run and only imports after explicit apply', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([[
            'display_name' => 'Treviso, Veneto, Italia',
            'boundingbox' => ['45.6', '45.7', '12.2', '12.3'],
        ]]),
        'overpass-api.de/*' => Http::response(['elements' => [
            ['type' => 'node', 'id' => 11, 'tags' => ['name' => 'Trattoria Demo', 'amenity' => 'restaurant', 'website' => 'https://demo.test', 'addr:city' => 'Treviso']],
            ['type' => 'way', 'id' => 12, 'tags' => ['name' => 'Bar Centrale', 'amenity' => 'bar']],
        ]]),
    ]);
    $workspace = 'test-'.uniqid();
    $discover = app(DiscoverProspectsInAreaAction::class);

    $preview = $discover->execute($workspace, 'Treviso');
    Assert::assertSame(2, $preview['found']);
    Assert::assertSame([], $preview['imported']);
    Assert::assertSame(0, AiProspect::query()->where('workspace', $workspace)->count());

    $saved = $discover->execute($workspace, 'Treviso', 200, true);
    Assert::assertSame(2, $saved['imported']['created']);
    $discover->execute($workspace, 'Treviso', 200, true);
    Assert::assertSame(2, AiProspect::query()->where('workspace', $workspace)->count());
    Http::assertSentCount(2); // one Nominatim + one Overpass; all subsequent reads cached
    Http::assertSent(fn (Request $r): bool => str_contains($r->url(), 'nominatim') && $r->hasHeader('User-Agent', 'TestProspecting/1.0 (+https://example.test)'));
    AiProspect::query()->where('workspace', $workspace)->delete();
})->group('AI-PROSPECTING-INV-33');

test('OSM cannot be contacted without independent provider authorization', function (): void {
    config(['ai.prospecting.public_discovery_enabled' => false]);
    Http::fake();
    expect(fn () => app(DiscoverProspectsInAreaAction::class)->execute('demo', 'Treviso'))
        ->toThrow(AiRequestException::class);
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');

test('OSM request rejects unapproved service endpoints and excessive areas', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([[
            'display_name' => 'Italia',
            'boundingbox' => ['35', '47', '6', '19'],
        ]]),
    ]);
    expect(fn () => app(OsmRequestAction::class)->execute('overpass', 'https://untrusted.example/api', [], 60))
        ->toThrow(AiRequestException::class);
    expect(fn () => app(GeocodeAreaAction::class)->execute('Italia'))
        ->toThrow(AiRequestException::class);
    Http::assertSentCount(1);
})->group('AI-PROSPECTING-INV-33');

test('remote research does not mutate a prospect; approved HTML remains usable offline', function (): void {
    Http::fake();
    $workspace = 'test-'.uniqid();
    $prospect = AiProspect::query()->create([
        'workspace' => $workspace,
        'fingerprint' => hash('sha256', 'offline-demo'),
        'name' => 'Trattoria Demo',
        'website' => 'https://demo.test',
        'domain' => 'demo.test',
        'source_provider' => 'openstreetmap',
        'source_url' => 'https://www.openstreetmap.org/node/11',
    ]);

    expect(fn () => app(ResearchProspectWebsiteAction::class)->execute($workspace, $prospect->id))
        ->toThrow(ValidationException::class);
    $prospect->refresh();
    Assert::assertSame(0, $prospect->score);
    Assert::assertNull($prospect->researched_at);
    Http::assertNothingSent();

    $research = app(ResearchProspectAction::class)->execute(
        $workspace, $prospect->id,
        '<p>Prenotazioni solo per telefono.</p><a href="/menu">Menu</a>',
        'https://demo.test',
    );
    Assert::assertSame('researched', $research->stage);
    Assert::assertSame(55, $research->score);
    Assert::assertContains('manual_booking_only', array_column($research->signals ?? [], 'code'));
    AiProspect::query()->where('workspace', $workspace)->delete();
})->group('AI-PROSPECTING-INV-33');

test('global prospecting switch blocks discovery even with provider authorization', function (): void {
    config(['ai.prospecting.enabled' => false]);
    Http::fake();
    expect(fn () => app(DiscoverProspectsInAreaAction::class)->execute('demo', 'Treviso'))
        ->toThrow(AiRequestException::class);
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');
