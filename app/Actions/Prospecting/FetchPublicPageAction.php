<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Spatie\QueueableAction\QueueableAction;

/**
 * Security gate for legacy live website audit (#33).
 *
 * Do not make an HTTP request until a transport is independently reviewed for:
 * DNS rebinding, IPv4/IPv6 egress, pinned connections, redirects and byte ceilings.
 * ResearchProspectAction still accepts operator-provided HTML offline.
 */
final class FetchPublicPageAction
{
    use QueueableAction;

    /**
     * @return array{ok: bool, url: string, status: int, html: string, seconds: float, error: string|null}
     */
    public function execute(string $url): array
    {
        return [
            'ok' => false,
            'url' => $url,
            'status' => 0,
            'html' => '',
            'seconds' => 0.0,
            'error' => 'remote_fetch_disabled_pending_ssrf_safe_adapter',
        ];
    }
}
