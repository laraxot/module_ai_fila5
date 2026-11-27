<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospect;

use Modules\AI\Data\Prospect\WebsiteAuditResultData;
use Modules\AI\Data\Prospect\WebsiteSignalData;
use Spatie\QueueableAction\QueueableAction;

use function Safe\json_decode;
use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\preg_replace;

/**
 * PROFILE-style public page audit (Sellbird patterns) + Closer prospector gaps.
 *
 * - execute($url): fail-closed until a reviewed SSRF-safe HTTP adapter exists
 * - analyzeHtml(): full deterministic signal catalog on already-fetched HTML
 *
 * Gap polarity feeds ranking; strength polarity is informational for briefs.
 */
final class DetectWebsiteSignalsAction
{
    use QueueableAction;

    public function execute(string $url, string $businessType = 'business'): WebsiteAuditResultData
    {
        return $this->fail($url, 'remote_fetch_disabled_pending_ssrf_safe_adapter');
    }

    /**
     * @param  array{page_url?: string|null}  $context  optional page URL for HTTPS / host hints
     * @return list<WebsiteSignalData>
     */
    public function analyzeHtml(
        string $html,
        float $seconds = 0.0,
        string $businessType = 'business',
        array $context = [],
    ): array {
        return $this->analyze($html, $seconds, $businessType, $context);
    }

    /**
     * Full audit result from offline HTML (preferred entry for tests and host fetchers).
     *
     * @param  array{page_url?: string|null}  $context
     */
    public function analyzeHtmlResult(
        string $html,
        float $seconds = 0.0,
        string $businessType = 'business',
        array $context = [],
    ): WebsiteAuditResultData {
        $url = isset($context['page_url']) && is_string($context['page_url'])
            ? $context['page_url']
            : 'offline://html';

        $signals = $this->analyze($html, $seconds, $businessType, $context);

        return new WebsiteAuditResultData(
            url: $url,
            ok: true,
            signals: $signals,
            title: $this->extractTitle($html),
            seconds: $seconds,
            fetchedAt: gmdate('c'),
        );
    }

    /**
     * @param  array{page_url?: string|null}  $context
     * @return list<WebsiteSignalData>
     */
    private function analyze(string $page, float $seconds, string $businessType, array $context): array
    {
        $found = [];
        $pageUrl = isset($context['page_url']) && is_string($context['page_url'])
            ? $context['page_url']
            : null;

        // --- Mobile ---
        if (! $this->hasViewport($page)) {
            $found[] = $this->gap('not-mobile');
        }

        // --- SEO / entity ---
        $schemaTypes = $this->jsonLdTypes($page);
        if (! $this->hasBusinessSchema($schemaTypes, $page, $businessType)) {
            $found[] = $this->gap('no-schema', evidence: $schemaTypes === [] ? 'no-json-ld' : implode(',', $schemaTypes));
        }

        $title = $this->extractTitle($page);
        if ($title === null || $title === '') {
            $found[] = $this->gap('no-title');
        }

        if (! preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=/i', $page)
            && ! preg_match('/<meta[^>]+content=[^>]+name=["\']description["\']/i', $page)) {
            $found[] = $this->gap('no-meta-description');
        }

        if (! preg_match('/<link[^>]+rel=["\']canonical["\']/i', $page)) {
            $found[] = $this->gap('no-canonical');
        }

        if (! preg_match('/property=["\']og:(title|image)["\']/i', $page)) {
            $found[] = $this->gap('no-og');
        }

        $h1Count = preg_match_all('/<h1\b/i', $page);
        if ($h1Count === 0) {
            $found[] = $this->gap('no-h1');
        } elseif ($h1Count > 1) {
            $found[] = $this->gap('multi-h1', evidence: 'count='.$h1Count);
        }

        if (! preg_match('/<html[^>]+\blang\s*=/i', $page)) {
            $found[] = $this->gap('no-lang');
        }

        // --- Performance (measured when seconds provided) ---
        if ($seconds >= WebsiteSignalCatalog::SLOW_SECONDS) {
            $found[] = new WebsiteSignalData(
                key: 'slow',
                score: WebsiteSignalCatalog::weight('slow'),
                why: sprintf('Slow observed load (~%.1f s, threshold %.1f s)', $seconds, WebsiteSignalCatalog::SLOW_SECONDS),
                confidence: 'measured',
                evidence: 'seconds='.$seconds,
                category: 'performance',
                polarity: 'gap',
            );
        }

        // --- Security hint from URL context ---
        if (is_string($pageUrl) && preg_match('#^http://#i', $pageUrl)) {
            $found[] = new WebsiteSignalData(
                key: 'no-https-hint',
                score: WebsiteSignalCatalog::weight('no-https-hint'),
                why: WebsiteSignalCatalog::defaultWhy('no-https-hint'),
                confidence: 'measured',
                evidence: $pageUrl,
                category: 'security',
                polarity: 'gap',
            );
        }

        // --- Trust / conversion ---
        if (! preg_match('/aggregateRating|reviewCount|testimonial|\breviews\b|trustpilot|google\.com\/maps/i', $page)) {
            $found[] = $this->gap('no-reviews');
        }

        $hasBooking = (bool) preg_match(
            '/calendly|acuityscheduling|hubspot\.com\/meetings|youcanbook|setmore|simplybook|'
            .'book.*(online|now|table)|schedule.*(call|demo|meeting)|prenota|riserva|'
            .'opentable|thefork|quandoo|resy|tock\.com/i',
            $page
        );
        if (! $hasBooking) {
            $found[] = $this->gap('no-booking');
        } else {
            $found[] = $this->strength('has-booking', 'Booking/scheduling marker present');
        }

        $hasChat = (bool) preg_match(
            '/intercom|tawk\.to|crisp\.chat|tidio|drift\.com|hubspot.*chat|zendesk|'
            .'livechat|whatsapp\.com\/send|wa\.me\//i',
            $page
        );
        if (! $hasChat) {
            $found[] = $this->gap('no-chat');
        } else {
            $found[] = $this->strength('has-chat', 'Chat/messaging widget marker present');
        }

        $hasForm = (bool) preg_match(
            '/<form\b[^>]*>/i',
            $page
        ) && (bool) preg_match(
            '/type=["\'](email|tel|text)["\']|name=["\'](email|name|phone|message)["\']|contact|demo|newsletter/i',
            $page
        );
        if (! $hasForm) {
            $found[] = $this->gap('no-contact-form');
        } else {
            $found[] = $this->strength('has-contact-form', 'Contact-like form present');
        }

        // --- Analytics / ads (stack) ---
        $hasAnalytics = (bool) preg_match(
            '/gtag\(|googletagmanager|google-analytics|GTM-|fbq\(|lintrk|'
            .'hotjar|clarity\.ms|segment\.com|mixpanel/i',
            $page
        );
        if (! $hasAnalytics) {
            $found[] = $this->gap('no-analytics');
        } else {
            $found[] = $this->strength('has-analytics', 'Analytics or ad pixel marker present');
        }

        // --- Commerce ICP ---
        if (WebsiteSignalCatalog::isCommerce($businessType)) {
            $hasCommerce = (bool) preg_match(
                '/stripe\.com|paddle\.com|shopify|woocommerce|bigcommerce|'
                .'add.to.cart|buy.now|\bcheckout\b|paypal\.com/i',
                $page
            );
            if (! $hasCommerce) {
                $found[] = $this->gap('no-commerce');
            } else {
                $found[] = $this->strength('has-commerce', 'Commerce/checkout marker present');
            }
        }

        // --- Hospitality ICP ---
        if (WebsiteSignalCatalog::isHospitality($businessType)) {
            if (! preg_match('/\bmenu\b|carta|food.?menu|our.?dishes|piatti/i', $page)) {
                $found[] = $this->gap('no-menu');
            }
            if (! preg_match('/opening.?hours|orari|hours.?of.?operation|OpeningHoursSpecification|chiudiamo|aperti/i', $page)) {
                $found[] = $this->gap('no-hours');
            }
            if (! preg_match(
                '/order.?online|ordina.?online|glovo|deliveroo|uber.?eats|just.?eat|'
                .'delivery|asporto|take.?away|qr.?order|ordina.?al.?tavolo/i',
                $page
            )) {
                $found[] = $this->gap('no-order-online');
            }
        }

        // --- Contact / presence ---
        if (! preg_match('/href=["\']tel:|\+\d{1,3}[\s\-.]?\d{2,}/i', $page)) {
            $found[] = $this->gap('no-phone');
        }

        if (! preg_match(
            '/facebook\.com|instagram\.com|linkedin\.com|twitter\.com|x\.com\/|youtube\.com|tiktok\.com/i',
            $page
        )) {
            $found[] = $this->gap('no-social');
        }

        // --- Maintenance ---
        if (preg_match('/(?:©|&copy;|copyright)\s*(20\d{2})/i', $page, $m)) {
            $year = (int) $m[1];
            $current = (int) gmdate('Y');
            if ($year < $current - 1) {
                $found[] = $this->gap('stale-copyright', evidence: 'year='.$year);
            } else {
                $found[] = $this->strength('fresh-copyright', 'Copyright year is current or last year');
            }
        }

        // Gap-only sort for ranking (strengths keep score 0)
        usort(
            $found,
            static function (WebsiteSignalData $a, WebsiteSignalData $b): int {
                $gapA = $a->polarity === 'gap' ? $a->score : -1;
                $gapB = $b->polarity === 'gap' ? $b->score : -1;

                return $gapB <=> $gapA ?: strcmp($a->key, $b->key);
            }
        );

        return $found;
    }

    private function gap(string $key, ?string $evidence = null, ?string $why = null): WebsiteSignalData
    {
        return new WebsiteSignalData(
            key: $key,
            score: WebsiteSignalCatalog::weight($key),
            why: $why ?? WebsiteSignalCatalog::defaultWhy($key),
            confidence: 'hint',
            evidence: $evidence,
            category: WebsiteSignalCatalog::category($key),
            polarity: 'gap',
        );
    }

    private function strength(string $key, string $why, ?string $evidence = null): WebsiteSignalData
    {
        return new WebsiteSignalData(
            key: $key,
            score: 0,
            why: $why,
            confidence: 'hint',
            evidence: $evidence,
            category: 'strength',
            polarity: 'strength',
        );
    }

    private function hasViewport(string $page): bool
    {
        if (! preg_match('/<meta[^>]+name=["\']viewport["\'][^>]*>/i', $page, $m)
            && ! preg_match('/<meta[^>]+content=[^>]+name=["\']viewport["\'][^>]*>/i', $page, $m)) {
            return false;
        }
        $tag = $m[0];

        // Weak viewport without device-width still counts as mobile risk
        if (! preg_match('/device-width/i', $tag)) {
            return false;
        }

        return true;
    }

    /** @return list<string> */
    private function jsonLdTypes(string $page): array
    {
        /** @var list<string> $types */
        $types = [];
        $result = preg_match_all(
            '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is',
            $page,
            $blocks
        );
        /** @see \Safe\preg_match_all() returns an int and throws on failure. */
        if ($result < 1) {
            return [];
        }

        foreach ($blocks[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim($json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (! is_array($decoded)) {
                continue;
            }
            $this->collectTypes($decoded, $types);
        }

        $unique = [];
        foreach ($types as $type) {
            if (! in_array($type, $unique, true)) {
                $unique[] = $type;
            }
        }

        return $unique;
    }

    /**
     * @param  array<string|int, mixed>  $node
     * @param  list<string>  $types
     */
    private function collectTypes(array $node, array &$types): void
    {
        if (isset($node['@type'])) {
            $t = $node['@type'];
            if (is_string($t)) {
                $types[] = $t;
            } elseif (is_array($t)) {
                foreach ($t as $item) {
                    if (is_string($item)) {
                        $types[] = $item;
                    }
                }
            }
        }

        if (isset($node['@graph']) && is_array($node['@graph'])) {
            foreach ($node['@graph'] as $child) {
                if (is_array($child)) {
                    $this->collectTypes($child, $types);
                }
            }
        }

        foreach ($node as $value) {
            if (is_array($value) && $this->isAssoc($value)) {
                $this->collectTypes($value, $types);
            }
        }
    }

    /** @param array<mixed> $arr */
    private function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /** @param list<string> $types */
    private function hasBusinessSchema(array $types, string $page, string $businessType): bool
    {
        $wanted = [
            'Organization', 'LocalBusiness', 'Corporation', 'ProfessionalService',
            'Restaurant', 'FoodEstablishment', 'CafeOrCoffeeShop', 'BarOrPub',
            'Store', 'OnlineStore', 'WebSite',
        ];

        foreach ($types as $type) {
            foreach ($wanted as $w) {
                if (strcasecmp($type, $w) === 0) {
                    return true;
                }
            }
        }

        // Fallback microdata / RDFa-ish strings
        if (preg_match('/schema\.org\/(Organization|LocalBusiness|Corporation|Restaurant|ProfessionalService)/i', $page)) {
            return true;
        }

        if (WebsiteSignalCatalog::isHospitality($businessType)
            && preg_match('/"@type"\s*:\s*"Restaurant"/i', $page)) {
            return true;
        }

        return false;
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
                new WebsiteSignalData(
                    key: 'no-site',
                    score: WebsiteSignalCatalog::weight('no-site'),
                    why: WebsiteSignalCatalog::defaultWhy('no-site'),
                    confidence: 'error',
                    evidence: $error,
                    category: 'reachability',
                    polarity: 'gap',
                ),
            ],
            error: $error,
            fetchedAt: gmdate('c'),
        );
    }
}
