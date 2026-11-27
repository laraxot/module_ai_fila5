<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Modules\AI\Actions\Prospect\DetectWebsiteSignalsAction;
use Modules\AI\Actions\Prospect\RankProspectLeadsAction;
use Modules\AI\Data\Prospect\ProspectLeadData;
use Modules\AI\Data\Prospect\WebsiteAuditResultData;
use Modules\AI\Data\Prospect\WebsiteSignalData;

test('a failed website audit never generates commercial ranking signals', function (): void {
    $failed = new WebsiteAuditResultData(
        url: 'https://audit.example',
        ok: false,
        signals: [new WebsiteSignalData('no-site', 4, 'Not reachable', 'error', '503')],
        error: 'HTTP 503',
    );

    expect($failed->totalScore())->toBe(0)
        ->and($failed->signalKeys())->toBe([])
        ->and($failed->toArray()['score'])->toBe(0);
});

test('unsafe direct website fetch is disabled and ranking does not reward failure', function (): void {
    Http::fake();

    $audit = app(DetectWebsiteSignalsAction::class)->execute('https://audit.example');
    expect($audit->ok)->toBeFalse()
        ->and($audit->error)->toBe('remote_fetch_disabled_pending_ssrf_safe_adapter');

    $lead = new ProspectLeadData(name: 'Azienda demo', website: 'https://audit.example', score: 45);
    $ranked = app(RankProspectLeadsAction::class)->execute([$lead]);

    expect($ranked)->toHaveCount(1)
        ->and($ranked[0]->score)->toBe(0)
        ->and($ranked[0]->signalKeys)->toBe([]);
    Http::assertNothingSent();
});
