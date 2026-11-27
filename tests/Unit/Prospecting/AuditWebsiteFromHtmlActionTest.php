<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Modules\AI\Actions\Prospecting\AuditWebsiteFromHtmlAction;

test('audit from html uses offline catalog and never hits the network', function (): void {
    Http::fake();

    $html = <<<'HTML'
    <!doctype html>
    <html lang="it">
    <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Trattoria Demo</title>
      <meta name="description" content="Cucina veneta">
      <link rel="canonical" href="https://demo.example/">
      <meta property="og:title" content="Trattoria Demo">
    </head>
    <body>
      <h1>Benvenuti</h1>
      <a href="tel:+390422000000">Chiama</a>
      <a href="https://instagram.com/demo">IG</a>
      <form><input type="email" name="email"></form>
    </body>
    </html>
    HTML;

    $audit = app(AuditWebsiteFromHtmlAction::class)->execute(
        $html,
        'https://demo.example/',
        0.4,
        'restaurant',
    );

    expect($audit->ok)->toBeTrue()
        ->and($audit->url)->toBe('https://demo.example/')
        ->and($audit->title)->toBe('Trattoria Demo')
        ->and($audit->totalScore())->toBeGreaterThan(0);

    Http::assertNothingSent();
});

test('audit from html ranks hospitality gaps when markers are missing', function (): void {
    $html = '<html><head><title>X</title></head><body><h1>X</h1></body></html>';

    $audit = app(AuditWebsiteFromHtmlAction::class)->execute(
        $html,
        'https://thin.example/',
        0.1,
        'restaurant',
    );

    $keys = $audit->signalKeys();

    expect($audit->ok)->toBeTrue()
        ->and($keys)->toContain('no-menu')
        ->and($keys)->toContain('no-hours')
        ->and($keys)->toContain('no-order-online');
});
