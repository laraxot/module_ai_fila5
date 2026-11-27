<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospect;

/**
 * Default gap weights and categories for public-page signals.
 * Host apps override via config('ai.prospect.signals.weights').
 *
 * Score = opportunity weight when the gap detector fires (higher = rank first).
 */
final class WebsiteSignalCatalog
{
    public const SLOW_SECONDS = 4.0;

    /**
     * @return array<string, array{weight: int, category: string, why: string}>
     */
    public static function defaults(): array
    {
        return [
            'not-mobile' => ['weight' => 3, 'category' => 'mobile', 'why' => 'Missing or weak viewport meta on examined page'],
            'no-schema' => ['weight' => 2, 'category' => 'seo', 'why' => 'No Organization/LocalBusiness (or ICP) JSON-LD on page'],
            'no-title' => ['weight' => 2, 'category' => 'seo', 'why' => 'Missing or empty <title>'],
            'no-meta-description' => ['weight' => 1, 'category' => 'seo', 'why' => 'No meta description on examined page'],
            'no-canonical' => ['weight' => 1, 'category' => 'seo', 'why' => 'No rel=canonical link'],
            'no-og' => ['weight' => 1, 'category' => 'seo', 'why' => 'No Open Graph title/image tags'],
            'no-h1' => ['weight' => 1, 'category' => 'seo', 'why' => 'No H1 heading found in HTML'],
            'multi-h1' => ['weight' => 1, 'category' => 'seo', 'why' => 'Multiple H1 headings on the same page'],
            'slow' => ['weight' => 2, 'category' => 'performance', 'why' => 'Slow observed load/TTFB for examined fetch'],
            'no-reviews' => ['weight' => 1, 'category' => 'trust', 'why' => 'No reviews/testimonials markers on page'],
            'no-booking' => ['weight' => 2, 'category' => 'conversion', 'why' => 'No online booking/scheduling pattern on page'],
            'no-chat' => ['weight' => 1, 'category' => 'conversion', 'why' => 'No live-chat / messaging widget markers'],
            'no-contact-form' => ['weight' => 2, 'category' => 'conversion', 'why' => 'No obvious contact/demo form on page'],
            'no-commerce' => ['weight' => 2, 'category' => 'commerce', 'why' => 'Commerce ICP but no clear checkout markers'],
            'no-analytics' => ['weight' => 1, 'category' => 'stack', 'why' => 'No common analytics tag on page'],
            'no-https-hint' => ['weight' => 3, 'category' => 'security', 'why' => 'Examined URL is not HTTPS'],
            'stale-copyright' => ['weight' => 1, 'category' => 'maintenance', 'why' => 'Copyright year looks older than last year'],
            'no-social' => ['weight' => 1, 'category' => 'presence', 'why' => 'No common social profile links on page'],
            'no-phone' => ['weight' => 1, 'category' => 'contact', 'why' => 'No tel: link or obvious phone pattern'],
            'no-lang' => ['weight' => 1, 'category' => 'a11y', 'why' => 'Missing html lang attribute'],
            'no-site' => ['weight' => 4, 'category' => 'reachability', 'why' => 'Site not reachable or fetch blocked'],
            // Restaurant / hospitality ICP extras (only when businessType matches)
            'no-menu' => ['weight' => 2, 'category' => 'hospitality', 'why' => 'No menu / carta markers on page'],
            'no-hours' => ['weight' => 1, 'category' => 'hospitality', 'why' => 'No opening-hours markers on page'],
            'no-order-online' => ['weight' => 3, 'category' => 'hospitality', 'why' => 'No online order / delivery pattern'],
        ];
    }

    public static function weight(string $key): int
    {
        $overrides = config('ai.prospect.signals.weights', []);
        if (is_array($overrides) && isset($overrides[$key]) && is_numeric($overrides[$key])) {
            return (int) $overrides[$key];
        }

        return self::defaults()[$key]['weight'] ?? 1;
    }

    public static function category(string $key): string
    {
        return self::defaults()[$key]['category'] ?? 'general';
    }

    public static function defaultWhy(string $key): string
    {
        return self::defaults()[$key]['why'] ?? $key;
    }

    /** @return list<string> */
    public static function hospitalityTypes(): array
    {
        return ['restaurant', 'cafe', 'bar', 'hospitality', 'food', 'trattoria', 'pizzeria'];
    }

    public static function isHospitality(string $businessType): bool
    {
        $t = strtolower($businessType);

        foreach (self::hospitalityTypes() as $h) {
            if (str_contains($t, $h)) {
                return true;
            }
        }

        return false;
    }

    public static function isCommerce(string $businessType): bool
    {
        return (bool) preg_match('/ecommerce|e-commerce|shop|store|commerce|retail/i', $businessType);
    }
}
