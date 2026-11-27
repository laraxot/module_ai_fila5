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
use Modules\AI\Actions\Prospecting\ResearchProspectWebsiteAction;
use Modules\AI\Actions\Prospecting\ResolveHostAction;
use Modules\AI\Exceptions\AiRequestException;
use Modules\AI\Models\AiProspect;
use Modules\AI\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

/*
 * #33 (lettura sicura dei siti) e ricerca dal vivo su OpenStreetMap, sopra l'import esistente.
 * Docs: Modules/AI/docs/prospecting.md
 */

beforeEach(function (): void {
    // SQLite in memoria vuoto: si creano solo le tabelle del prospecting, e solo se il driver e' sqlite.
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
        'ai.prospecting.site_fetch_enabled' => true, // explicit test-only experimental opt-in (#33)
        'ai.prospecting.user_agent' => 'TestProspecting/1.0 (+https://example.test)',
    ]);
    // DNS finto: i domini di prova risolvono su un IP pubblico, alcuni su reti private.
    app()->instance(ResolveHostAction::class, new class extends ResolveHostAction
    {
        public function execute(string $host): array
        {
            return match ($host) {
                'interno.test' => ['192.168.1.10'],
                'metadata.test' => ['169.254.169.254'],
                'nessuno.test' => [],
                default =test('network website fetch stays disabled even with the experimental flag enabled', function (): void {
    Http::fake();
    config(['ai.prospecting.site_fetch_enabled' => true]);

    $fetch = app(FetchPublicPageAction::class);
    foreach (['https://public.test', 'http://127.0.0.1:8000', 'http://metadata.test/latest',
        'http://[::1]/', 'https://redirige.test'] as $url) {
        $result = $fetch->execute($url);
        Assert::assertFalse($result['ok']);
        Assert::assertSame(0, $result['status']);
        Assert::assertSame('', $result['html']);
        Assert::assertSame('remote_fetch_disabled_pending_ssrf_safe_adapter', $result['error']);
    }
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');

url(), '127.0.0.1'));
})->group('AI-PROSPECTING-INV-33');

test('discovers businesses on openstreetmap respecting usage rules and imports them once', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([['display_name' => 'Treviso, Veneto, Italia', 'boundingbox' => ['45.6', '45.7', '12.2', '12.3']]]),
        'overpass-api.de/*' => Http::response(['elements' => [
            ['type' => 'node', 'id' => 11, 'tags' => ['name' => 'Trattoria Da Bepi', 'amenity' => 'restaurant', 'website' => 'https://dabepi.test', 'addr:city' => 'Treviso']],
            ['type' => 'way', 'id' => 12, 'center' => ['lat' => 45.6, 'lon' => 12.2], 'tags' => ['name' => 'Bar Centrale', 'amenity' => 'bar']],
            ['type' => 'node', 'id' => 13, 'tags' => ['name' => 'Ferramenta', 'shop' => 'hardware']],
        ]]),
    ]);
    $workspace = 'test-'.uniqid();

    $first = app(DiscoverProspectsInAreaAction::class)->execute($workspace, 'Treviso', 200, true);
    app(DiscoverProspectsInAreaAction::class)->execute($workspace, 'Treviso');

    Assert::assertSame('Treviso, Veneto, Italia', $first['area']);
    Assert::assertSame(2, $first['found']);
    Assert::assertSame(2, AiProspect::query()->where('workspace', $workspace)->count());
    Assert::assertSame('openstreetmap', AiProspect::query()->where('workspace', $workspace)->value('source_provider'));
    // seconda ricerca uguale: tutto dalla cache, nessuna nuova richiesta ai servizi OSM
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $r): bool => str_contains($r->url(), 'nominatim') && $r->hasHeader('User-Agent', 'TestProspecting/1.0 (+https://example.test)'));

    AiProspect::query()->where('workspace', $test('remote research cannot mutate a prospect, while approved HTML can be analyzed offline', function (): void {
    Http::fake();
    $workspace = 'test-'.uniqid();
    $prospect = AiProspect::query()->create([
        'workspace' => $workspace,
        'fingerprint' => hash('sha256', 'offline-demo'),
        'name' => 'Da Bepi',
        'website' => 'https://dabepi.test',
        'domain' => 'dabepi.test',
        'source_provider' => 'openstreetmap',
        'source_url' => 'https://www.openstreetmap.org/node/11',
    ]);
    expect(fn () => app(ResearchProspectWebsiteAction::class)->execute($workspace, $prospect->id))
        ->toThrow(ValidationException::class);
    $prospect->refresh();
    Assert::assertSame(0, $prospect->score);
    Assert::assertNull($prospect->researched_at);
    Http::assertNothingSent();

    $researched = app(\Modules\AI\Actions\Prospecting\ResearchProspectAction::class)->execute(
        $workspace, $prospect->id,
        '<p>Prenotazioni solo per telefono.</p><a href="/menu">Menu</a>',
        'https://dabepi.test',
    );
    Assert::assertSame('researched', $researched->stage);
    Assert::assertContains('manual_booking_only', array_column($researched->signals ?? [], 'code'));
    Assert::assertSame(55, $researched->score);
    AiProspect::query()->where('workspace', $workspace)->delete();
})->group('AI-PROSPECTING-INV-33');

workspace)->delete();
})->group('AI-PROSPECTING-INV-33');

test('stays off until the project enables it', function (): void {
    config(['ai.prospecting.enabled' => false]);
    Http::fake();

    expect(fn () => app(DiscoverProspectsInAreaAction::class)->execute('x', 'Treviso'))->toThrow(AiRequestException::class, 'spenta');
    expect(fn () => app(ResearchProspectWebsiteAction::class)->execute('x', 1))->toThrow(ValidationException::class);
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');
