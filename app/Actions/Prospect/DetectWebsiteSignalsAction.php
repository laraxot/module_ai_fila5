<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospect;

use Illuminate\Support\Facades\Http;
use Modules\AI\Data\Prospect\WebsiteAuditResultData;
use Modules\AI\Data\Prospect\WebsiteSignalData;
use Spatie\QueueableAction\QueueableAction;

/**
 * PROFILE stage (Sellbird) + closer prospector audit.
 * One public page, deterministic regex signals, SSRF-safe host filter.
 * Absence of a pattern is a *hint* only when the page was successfully fetched.
 */
final class DetectWebsiteSignalsAction
{
    use QueueableAction;

    private const USER_AGENT = 'LaraxotAI-Prospector/1.0 (+https://github.com/laraxot/module_ai_fila5; public HTML only)';

    private const SLOW_SECONDS = 4.0;

    public function execute(string $url, string $businessType = 'business'): WebsiteAuditResultData
    {
        $normalized = $this->normalizeUrl($url);
        if ($normalized === null) {
            return $this->fail($url, 'invalid_or_blocked_url');
        }

        $started = microtime(true);

        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml',
            ])
                ->timeout(15)
                ->withOptions(['allow_redirects' => ['max' => 3]])
                ->get($normalized);
        } catch (\Throwable $e) {
            return $this->fail($normalized, substr($e->getMessage(), 0, 120));
        }

        if (! $response->successful()) {
            return $this->fail($normalized, 'HTTP '.$response->status());
        }

        $seconds = round(microtime(true) - $started, 1);
        $body = mb_substr($response->body(), 0, 2_000_000);

        return new WebsiteAuditResultData(
            url: $normalized,
            ok: true,
            signals: $this->analyze($body, $seconds, $businessType),
            title: $this->extractTitle($body),
            seconds: $seconds,
            fetchedAt: gmdate('c'),
        );
    }

    /** @return list<WebsiteSignalData> */
    public function analyzeHtml(string $html, float $seconds = 0.0, string $businessType = 'business'): array
    {
        return $this->analyze($html, $seconds, $businessType);
    }

    /** @return list<WebsiteSignalData> */
    private function analyze(string $page, float $seconds, string $businessType): array
    {
        $found = [];

        if (! preg_match('/<meta[^>]+name=["\']viewport["\'][^>]*>/i', $page)) {
            $found[] = new WebsiteSignalData('not-mobile', 3, 'Missing viewport meta (likely not mobile-friendly)', 'hint', 'meta[name=viewport]');
        }

        if (! preg_match('/schema\.org\/(Organization|LocalBusiness|Corporation|Restaurant|ProfessionalService)/i', $page)
            && ! preg_match('/"@type"\s*:\s*"(Organization|LocalBusiness|Corporation|Restaurant)"/i', $page)) {
            $found[] = new WebsiteSignalData('no-schema', 2, 'No Organization/LocalBusiness schema detected', 'hint', 'json-ld/schema.org');
        }

        if ($seconds >= self::SLOW_SECONDS) {
            $found[] = new WebsiteSignalData('slow', 2, sprintf('Slow TTFB/load (~%.0f s)', $seconds), 'measured', 'http_timing');
        }

        if (! preg_match('/aggregateRating|reviewCount|testimonial|reviews/i', $page)) {
            $found[] = new WebsiteSignalData('no-reviews', 1, 'No reviews block on examined page', 'hint', 'html');
        }

        // Booking / ordering patterns are industry-agnostic detectors; host app ranks by ICP.
        if (! preg_match('/calendly|acuityscheduling|hubspot\.com\/meetings|book.*(online|now)|schedule.*(call|demo|meeting)|prenota|riserva/i', $page)) {
            $found[] = new WebsiteSignalData('no-booking', 2, 'No online booking/scheduling pattern on page', 'hint', 'html');
        }

        if (! preg_match('/stripe\.com|paddle\.com|shopify|woocommerce|add.to.cart|buy.now|checkout/i', $page)
            && preg_match('/ecommerce|shop|store|commerce/i', $businessType)) {
            $found[] = new WebsiteSignalData('no-commerce', 2, 'Commerce ICP but no clear checkout markers', 'hint', 'html');
        }

        if (! preg_match('/gtag\(|googletagmanager|google-analytics|GTM-|fbq\(/i', $page)) {
            $found[] = new WebsiteSignalData('no-analytics', 1, 'No common analytics tag on page', 'hint', 'html');
        }

        usort($found, static fn (WebsiteSignalData $a, WebsiteSignalData $b): int => $b->score <=> $a->score);

        return $found;
    }

    private function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }
        if (preg_match('/^(localhost|127\.|10\.|192\.168\.|169\.254\.|0\.0\.0\.0|\[::1\])/i', $host)) {
            return null;
        }

        return $url;
    }

    private function extractTitle(string $html): ?string
    {
        if (! preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            return null;
        }
        $title = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $title) ?? $title) ?: null;
    }

    private function fail(string $url, string $error): WebsiteAuditResultData
    {
        return new WebsiteAuditResultData(
            url: $url,
            ok: false,
            signals: [
                new WebsiteSignalData('no-site', 4, 'Site not reachable or blocked', 'error', $error),
            ],
            error: $error,
            fetchedAt: gmdate('c'),
        );
    }
}
