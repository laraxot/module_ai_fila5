<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospect;

use Modules\AI\Data\Prospect\WebsiteAuditResultData;
use Modules\AI\Data\Prospect\WebsiteSignalData;
use Spatie\QueueableAction\QueueableAction;

/**
 * PROFILE stage (Sellbird) + closer prospector audit.
 * Offline deterministic regex signals only. Direct HTTP fetching fails closed.
 * Absence of a pattern is never proof of missing capabilities.
 */
final class DetectWebsiteSignalsAction
{
    use QueueableAction;

    private const SLOW_SECONDS = 4.0;

    public function execute(string $url, string $businessType = 'business'): WebsiteAuditResultData
    {
        // Fail closed: direct outbound fetch cannot safely guard DNS rebinding,
        // redirects, or private destinations without a reviewed HTTP adapter.
        // Call analyzeHtml() with a permitted, already-fetched page instead.
        return $this->fail($url, 'remote_fetch_disabled_pending_ssrf_safe_adapter');
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
