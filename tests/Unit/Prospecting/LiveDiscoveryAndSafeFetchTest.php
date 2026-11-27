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
                default => ['93.184.216.34'],
            };
        }
    });
});

test('denies direct website fetch unless the separate experimental gate is enabled', function (): void {
    config(['ai.prospecting.site_fetch_enabled' => false]);
    Http::fake(['*' => Http::response('Should never be fetched')]);

    $result = app(FetchPublicPageAction::class)->execute('https://public.test');

    Assert::assertFalse($result['ok']);
    Assert::assertSame('lettura siti disabilitata', $result['error']);
    Assert::assertSame('', $result['html']);
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');

test('refuses private, metadata and malformed addresses, also after a redirect', function (): void {
    Http::fake([
        'https://redirige.test*' => Http::response('', 302, ['Location' => 'http://interno.test/admin']),
        '*' => Http::response('ok'),
    ]);
    $fetch = app(FetchPublicPageAction::class);

    Assert::assertSame('indirizzo non pubblico', $fetch->execute('http://interno.test')['error']);
    Assert::assertSame('indirizzo non pubblico', $fetch->execute('http://metadata.test/latest')['error']);
    Assert::assertSame('indirizzo non pubblico', $fetch->execute('http://127.0.0.1:8000')['error']);
    Assert::assertSame('indirizzo non pubblico', $fetch->execute('http://[::1]/')['error']);
    Assert::assertSame('indirizzo non valido', $fetch->execute('file:///etc/passwd')['error']);
    Assert::assertSame('dominio inesistente', $fetch->execute('https://nessuno.test')['error']);
    Assert::assertSame('indirizzo non pubblico', $fetch->execute('https://redirige.test')['error']);
    Http::assertNotSent(fn (Request $r): bool => str_contains($r->url(), 'interno.test') || str_contains($r->url(), '127.0.0.1'));
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

    $first = app(DiscoverProspectsInAreaAction::class)->execute($workspace, 'Treviso');
    app(DiscoverProspectsInAreaAction::class)->execute($workspace, 'Treviso');

    Assert::assertSame('Treviso, Veneto, Italia', $first['area']);
    Assert::assertSame(2, $first['found']);
    Assert::assertSame(2, AiProspect::query()->where('workspace', $workspace)->count());
    Assert::assertSame('openstreetmap', AiProspect::query()->where('workspace', $workspace)->value('source_provider'));
    // seconda ricerca uguale: tutto dalla cache, nessuna nuova richiesta ai servizi OSM
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $r): bool => str_contains($r->url(), 'nominatim') && $r->hasHeader('User-Agent', 'TestProspecting/1.0 (+https://example.test)'));

    AiProspect::query()->where('workspace', $workspace)->delete();
})->group('AI-PROSPECTING-INV-33');

test('reads a prospect website safely and never scores a site that could not be read', function (): void {
    Http::fake([
        'https://dabepi.test*' => Http::response('<html><body>Prenotazioni solo per telefono. <a href="/menu">Menu</a></body></html>'),
        'https://giu.test*' => Http::response('', 500),
    ]);
    $workspace = 'test-'.uniqid();
    $ok = AiProspect::query()->create(['workspace' => $workspace, 'fingerprint' => hash('sha256', 'a'), 'name' => 'Da Bepi', 'website' => 'https://dabepi.test', 'domain' => 'dabepi.test', 'source_provider' => 'openstreetmap', 'source_url' => 'https://www.openstreetmap.org/node/11']);
    $down = AiProspect::query()->create(['workspace' => $workspace, 'fingerprint' => hash('sha256', 'b'), 'name' => 'Giu', 'website' => 'https://giu.test', 'domain' => 'giu.test', 'source_provider' => 'openstreetmap', 'source_url' => 'https://www.openstreetmap.org/node/12']);

    $researched = app(ResearchProspectWebsiteAction::class)->execute($workspace, $ok->id);

    Assert::assertSame('researched', $researched->stage);
    Assert::assertContains('manual_booking_only', array_column($researched->signals ?? [], 'code'));
    Assert::assertGreaterThan(0, $researched->score);

    expect(fn () => app(ResearchProspectWebsiteAction::class)->execute($workspace, $down->id))->toThrow(ValidationException::class);
    $down->refresh();
    Assert::assertSame(0, $down->score);
    Assert::assertNull($down->researched_at);

    AiProspect::query()->where('workspace', $workspace)->delete();
})->group('AI-PROSPECTING-INV-33');

test('stays off until the project enables it', function (): void {
    config(['ai.prospecting.enabled' => false]);
    Http::fake();

    expect(fn () => app(DiscoverProspectsInAreaAction::class)->execute('x', 'Treviso'))->toThrow(AiRequestException::class, 'spenta');
    expect(fn () => app(ResearchProspectWebsiteAction::class)->execute('x', 1))->toThrow(ValidationException::class);
    Http::assertNothingSent();
})->group('AI-PROSPECTING-INV-33');
