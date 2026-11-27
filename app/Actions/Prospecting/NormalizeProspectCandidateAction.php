<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Validation\ValidationException;
use Spatie\QueueableAction\QueueableAction;

/**
 * Normalizes business-only input. URLs are metadata: this action never fetches them.
 */
final class NormalizeProspectCandidateAction
{
    use QueueableAction;

    /**
     * @param array<string, mixed> $candidate
     * @return array{workspace: string, fingerprint: string, name: string, website: ?string, domain: ?string, category: ?string, city: ?string, country: ?string, source_provider: string, source_url: string}
     */
    public function execute(string $workspace, array $candidate): array
    {
        $workspace = trim($workspace);
        $name = trim((string) ($candidate['name'] ?? ''));
        if ($workspace === '' || strlen($workspace) > 96 || $name === '' || mb_strlen($name) > 255) {
            throw ValidationException::withMessages(['prospect' => 'Workspace e nome azienda sono obbligatori e limitati.']);
        }

        $sourceUrl = $this->url($candidate['source_url'] ?? null, 'source_url');
        if ($sourceUrl === null) {
            throw ValidationException::withMessages(['source_url' => 'Una fonte pubblica verificabile e obbligatoria.']);
        }

        $website = $this->url($candidate['website'] ?? null, 'website');
        $domain = $website === null ? null : $this->domain($website);
        $city = $this->short($candidate['city'] ?? null);
        $country = $this->short($candidate['country'] ?? null);
        $category = $this->short($candidate['category'] ?? null);
        if (mb_strlen($city ?? '') > 255 || mb_strlen($category ?? '') > 255) {
            throw ValidationException::withMessages(['prospect' => 'Categoria o citta troppo lunghe.']);
        }
        $country = $country === null ? null : strtoupper($country);
        if ($country !== null && ! preg_match('/^[A-Z]{2}$/D', $country)) {
            throw ValidationException::withMessages(['country' => 'Usare un codice paese ISO a due lettere.']);
        }

        $provider = $this->short($candidate['source_provider'] ?? null) ?? 'manual';
        if (strlen($provider) > 64) {
            throw ValidationException::withMessages(['source_provider' => 'Nome provider troppo lungo.']);
        }

        $identity = $domain === null
            ? 'name:'.mb_strtolower($name).'|city:'.mb_strtolower($city ?? '').'|country:'.($country ?? '')
            : 'domain:'.$domain;

        return [
            'workspace' => $workspace,
            'fingerprint' => hash('sha256', $identity),
            'name' => $name,
            'website' => $website,
            'domain' => $domain,
            'category' => $category,
            'city' => $city,
            'country' => $country,
            'source_provider' => $provider,
            'source_url' => $sourceUrl,
        ];
    }

    /** @return non-empty-string|null */
    private function short(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function url(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value) || strlen($value) > 2048 || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([$field => 'URL pubblico HTTP(S) non valido.']);
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        $port = parse_url($value, PHP_URL_PORT);
        $username = parse_url($value, PHP_URL_USER);
        $password = parse_url($value, PHP_URL_PASS);
        if (! in_array($scheme, ['http', 'https'], true)
            || $host === '' || filter_var($host, FILTER_VALIDATE_IP) !== false
            || $host === 'localhost' || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal') || ! str_contains($host, '.')
            || $username !== null || $password !== null
            || ($port !== null && ! in_array($port, [80, 443], true))) {
            throw ValidationException::withMessages([$field => 'URL esterno pubblico richiesto.']);
        }

        return trim($value);
    }

    private function domain(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
