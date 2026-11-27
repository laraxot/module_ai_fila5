<?php

declare(strict_types=1);

use Modules\AI\Actions\Prospect\DetectWebsiteSignalsAction;

test('flags missing viewport and schema on minimal html', function (): void {
    $html = '<html><head><title>Acme Co</title></head><body><p>Hello</p></body></html>';
    $signals = app(DetectWebsiteSignalsAction::class)->analyzeHtml($html, 0.4, 'business');
    $keys = array_map(static fn ($s) => $s->key, $signals);

    expect($keys)->toContain('not-mobile')
        ->and($keys)->toContain('no-schema');
});

test('does not flag viewport when present', function (): void {
    $html = '<html><head><meta name="viewport" content="width=device-width">'
        .'<script type="application/ld+json">{"@type":"Organization"}</script>'
        .'</head><body><a href="https://calendly.com/x">Book</a></body></html>';
    $keys = array_map(
        static fn ($s) => $s->key,
        app(DetectWebsiteSignalsAction::class)->analyzeHtml($html, 0.2, 'business')
    );

    expect($keys)->not->toContain('not-mobile')
        ->and($keys)->not->toContain('no-schema')
        ->and($keys)->not->toContain('no-booking');
});
