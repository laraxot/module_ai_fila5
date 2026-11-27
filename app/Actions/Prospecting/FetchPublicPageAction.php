<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Safe\Exceptions\UrlException;
use Spatie\QueueableAction\QueueableAction;

use function Safe\parse_url;
use function Safe\preg_match;

/**
 * Adapter SPERIMENTALE per leggere UNA pagina pubblica di un prospect.
 * Disabilitato per default: richiede ai.prospecting.site_fetch_enabled = true (#33).
 * Le verifiche in questa classe NON garantiscono la protezione SSRF, DNS rebinding,
 * IPv6/AAAA o il limite effettivo dei byte scaricati. Non usare in produzione.
 *
 * - Solo http/https e solo indirizzi pubblici: host che risolvono su reti private, locali o riservate
 *   (127.0.0.1, 10.x, 192.168.x, 169.254.169.254...) vengono rifiutati, anche dopo un redirect.
 * - Redirect seguiti a mano (max 3), ognuno ricontrollato.
 * - User-Agent che identifica chi legge; tempo misurato (serve al segnale "sito lento"); stringa HTML troncata a
 *   `ai.prospecting.max_bytes`.
 *
 * Il contenuto restituito NON e' fidato: puo' contenere istruzioni rivolte a un modello (prompt injection).
 * Chi lo passa a un modello lo deve trattare come dato (vedi DraftOutreachAction).
 */
final class FetchPublicPageAction
{
    use QueueableAction;

    public function __construct(private readonly ResolveHostAction $resolve) {}

    /**
     * @return array{ok: bool, url: string, status: int, html: string, seconds: float, error: string|null}
     */
    public function execute(string $url): array
    {
        $timeout = config()->integer('ai.prospecting.site_timeout', 8);
        $maxBytes = config()->integer('ai.prospecting.max_bytes', 1_500_000);
        $userAgent = config('ai.prospecting.user_agent');
        $started = microtime(true);
        // P0 (#33): callers must explicitly opt in to experimental live fetching.
        // The prospecting feature flag alone does not authorize outbound HTTP.
        if (! config()->boolean('ai.prospecting.site_fetch_enabled', false)) {
            return $this->result(false, $url, 0, '', $started, 'lettura siti disabilitata');
        }

        $current = $url;

        for ($hop = 0; $hop <= 3; $hop++) {
            $problem = $this->unsafe($current);
            if ($problem !== null) {
                return $this->result(false, $current, 0, '', $started, $problem);
            }

            try {
                $response = Http::timeout($timeout)
                    ->withoutRedirecting()
                    ->withHeaders([
                        'User-Agent' => \is_string($userAgent) && $userAgent !== '' ? $userAgent : 'LaraxotProspecting/1.0',
                        'Accept' => 'text/html,application/xhtml+xml',
                    ])
                    ->get($current);
            } catch (ConnectionException $e) {
                return $this->result(false, $current, 0, '', $started, 'irraggiungibile');
            }

            $location = $response->header('Location');
            if ($response->redirect() && $location !== '') {
                $current = $this->absolute($current, $location);

                continue;
            }

            $html = substr($response->body(), 0, max(1, $maxBytes));

            return $this->result($response->successful(), $current, $response->status(), $html, $started, $response->successful() ? null : 'HTTP '.$response->status());
        }

        return $this->result(false, $current, 0, '', $started, 'troppi redirect');
    }

    private function unsafe(string $url): ?string
    {
        $scheme = strtolower($this->part($url, PHP_URL_SCHEME));
        $host = $this->part($url, PHP_URL_HOST);
        if (! \in_array($scheme, ['http', 'https'], true) || $host === '') {
            return 'indirizzo non valido';
        }

        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : $this->resolve->execute($host);
        if ($ips === []) {
            return 'dominio inesistente';
        }
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return 'indirizzo non pubblico';
            }
        }

        return null;
    }

    private function absolute(string $base, string $location): string
    {
        if (preg_match('#^https?://#i', $location) === 1) {
            return $location;
        }
        $origin = ($this->part($base, PHP_URL_SCHEME) ?: 'https').'://'.$this->part($base, PHP_URL_HOST);

        return str_starts_with($location, '/') ? $origin.$location : rtrim($base, '/').'/'.$location;
    }

    /** Una parte dell'URL come stringa ('' se manca o se l'URL e' malformato). */
    private function part(string $url, int $component): string
    {
        try {
            $value = parse_url($url, $component);
        } catch (UrlException) {
            return '';
        }

        return \is_string($value) ? $value : '';
    }

    /**
     * @return array{ok: bool, url: string, status: int, html: string, seconds: float, error: string|null}
     */
    private function result(bool $ok, string $url, int $status, string $html, float $started, ?string $error): array
    {
        return ['ok' => $ok, 'url' => $url, 'status' => $status, 'html' => $html, 'seconds' => round(microtime(true) - $started, 2), 'error' => $error];
    }
}
