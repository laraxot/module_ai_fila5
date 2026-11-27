<?php

declare(strict_types=1);

use Modules\AI\Actions\Prospect\DetectWebsiteSignalsAction;
use Modules\AI\Actions\Prospect\WebsiteSignalCatalog;

test('minimal html raises core seo and mobile gaps', function (): void {
    $html = '<html><head></head><body><p>Hello</p></body></html>';
    $signals = app(DetectWebsiteSignalsAction::class)->analyzeHtml($html, 0.4, 'business');
    $keys = array_map(static fn ($s) => $s->key, $signals);

    expect($keys)->toContain('not-mobile')
        ->and($keys)->toContain('no-schema')
        ->and($keys)->toContain('no-title')
        ->and($keys)->toContain('no-h1')
        ->and($keys)->toContain('no-lang');
});

test('viewport requires device-width', function (): void {
    $html = '<html lang="en"><head><meta name="viewport" content="width=1024"><title>X</title></head><body><h1>X</h1></body></html>';
    $keys = array_map(
        static fn ($s) => $s->key,
        app(DetectWebsiteSignalsAction::class)->analyzeHtml($html)
    );

    expect($keys)->toContain('not-mobile');
});

test('modern page clears mobile schema booking and emits strengths', function (): void {
    $html = <<<'HTML'
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Acme services">
  <link rel="canonical" href="https://acme.example/">
  <meta property="og:title" content="Acme">
  <meta property="og:image" content="https://acme.example/og.png">
  <title>Acme Co</title>
  <script type="application/ld+json">{"@type":"Organization","name":"Acme"}</script>
  <script>gtag('config','G-XXX');</script>
</head>
<body>
  <h1>Acme</h1>
  <a href="https://calendly.com/acme/demo">Book demo</a>
  <a href="https://linkedin.com/company/acme">LinkedIn</a>
  <a href="tel:+390000000">Call</a>
  <form action="/contact"><input type="email" name="email"><button>Send</button></form>
  <p>© 2026 Acme</p>
</body>
</html>
HTML;

    $signals = app(DetectWebsiteSignalsAction::class)->analyzeHtml($html, 0.2, 'business');
    $byKey = [];
    foreach ($signals as $s) {
        $byKey[$s->key] = $s;
    }

    expect($byKey)->not->toHaveKey('not-mobile')
        ->and($byKey)->not->toHaveKey('no-schema')
        ->and($byKey)->not->toHaveKey('no-booking')
        ->and($byKey)->toHaveKey('has-booking')
        ->and($byKey['has-booking']->polarity)->toBe('strength')
        ->and($byKey)->toHaveKey('has-analytics')
        ->and($byKey)->toHaveKey('fresh-copyright');
});

test('json-ld @graph Organization counts as schema', function (): void {
    $html = '<html lang="en"><head><title>G</title>'
        .'<meta name="viewport" content="width=device-width">'
        .'<script type="application/ld+json">{"@graph":[{"@type":"Organization","name":"G"}]}</script>'
        .'</head><body><h1>G</h1></body></html>';

    $keys = array_map(
        static fn ($s) => $s->key,
        app(DetectWebsiteSignalsAction::class)->analyzeHtml($html)
    );

    expect($keys)->not->toContain('no-schema');
});

test('hospitality type adds menu hours order gaps', function (): void {
    $html = '<html lang="it"><head><title>Trattoria</title>'
        .'<meta name="viewport" content="width=device-width"></head>'
        .'<body><h1>Trattoria</h1><p>Benvenuti</p></body></html>';

    $keys = array_map(
        static fn ($s) => $s->key,
        app(DetectWebsiteSignalsAction::class)->analyzeHtml($html, 0.1, 'restaurant')
    );

    expect($keys)->toContain('no-menu')
        ->and($keys)->toContain('no-hours')
        ->and($keys)->toContain('no-order-online');
});

test('http page_url yields measured no-https-hint', function (): void {
    $html = '<html lang="en"><head><title>X</title><meta name="viewport" content="width=device-width"></head><body><h1>X</h1></body></html>';
    $signals = app(DetectWebsiteSignalsAction::class)->analyzeHtml(
        $html,
        0.1,
        'business',
        ['page_url' => 'http://insecure.example/']
    );
    $https = collect($signals)->firstWhere('key', 'no-https-hint');

    expect($https)->not->toBeNull()
        ->and($https?->confidence)->toBe('measured');
});

test('slow seconds is measured gap', function (): void {
    $html = '<html lang="en"><head><title>X</title><meta name="viewport" content="width=device-width"></head><body><h1>X</h1></body></html>';
    $slow = collect(app(DetectWebsiteSignalsAction::class)->analyzeHtml($html, 5.2))
        ->firstWhere('key', 'slow');

    expect($slow)->not->toBeNull()
        ->and($slow?->confidence)->toBe('measured')
        ->and($slow?->score)->toBe(WebsiteSignalCatalog::weight('slow'));
});

test('execute remains fail-closed', function (): void {
    $result = app(DetectWebsiteSignalsAction::class)->execute('https://example.com');

    expect($result->ok)->toBeFalse()
        ->and($result->signals[0]->key)->toBe('no-site')
        ->and($result->signals[0]->confidence)->toBe('error');
});

test('gap score sums exclude strengths', function (): void {
    $html = <<<'HTML'
<html lang="en"><head>
<meta name="viewport" content="width=device-width">
<title>Acme</title>
<script type="application/ld+json">{"@type":"Organization"}</script>
</head><body><h1>Acme</h1>
<a href="https://calendly.com/x">Book</a>
</body></html>
HTML;
    $result = app(DetectWebsiteSignalsAction::class)->analyzeHtmlResult($html, 0.1);
    $gapScore = 0;
    foreach ($result->signals as $s) {
        if ($s->polarity === 'gap') {
            $gapScore += $s->score;
        }
    }

    expect($result->totalScore())->toBeGreaterThanOrEqual($gapScore);
});
