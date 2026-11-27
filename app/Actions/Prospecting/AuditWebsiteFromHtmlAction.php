<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Modules\AI\Actions\Prospect\DetectWebsiteSignalsAction;
use Modules\AI\Data\Prospect\WebsiteAuditResultData;
use Spatie\QueueableAction\QueueableAction;

/**
 * Consolidation bridge (#40): Prospecting callers supply already-fetched public HTML;
 * Prospect offline detector owns the signal catalog. No HTTP here.
 *
 * Host / future SSRF-safe adapter: FetchPublicPageAction → this action → rank/brief.
 */
final class AuditWebsiteFromHtmlAction
{
    use QueueableAction;

    public function __construct(
        private readonly DetectWebsiteSignalsAction $detect,
    ) {}

    public function execute(
        string $html,
        string $pageUrl,
        float $seconds = 0.0,
        string $businessType = 'business',
    ): WebsiteAuditResultData {
        return $this->detect->analyzeHtmlResult(
            $html,
            $seconds,
            $businessType,
            ['page_url' => $pageUrl],
        );
    }
}
