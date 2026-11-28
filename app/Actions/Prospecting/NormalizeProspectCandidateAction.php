<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Validation\ValidationException;
use Modules\AI\Data\Prospect\ProspectLeadData;
use Spatie\QueueableAction\QueueableAction;

use function Safe\parse_url;

/** Compatibility adapter; prospect identity and data remain canonical.
 *
 * @see docs/adr/0004-phpstan-zero-errori.md — `Safe\parse_url` (mai `false` silenzioso su URL malformati)
 *      e array-shape `{website, source_url, domain}` nel ritorno
 * @see https://phpstan.org/error-identifiers/theCodingMachineSafe.function
 */
final class NormalizeProspectCandidateAction
{
    use QueueableAction;

    /**
     * @param  array<string, mixed>  $candidate
     * @return array{website: string|null, source_url: string, domain: string|null}
     */
    public function execute(string $_workspace, array $candidate): array
    {
        $lead = ProspectLeadData::fromArray($candidate);
        if ($lead->name === '' || $lead->sourceUrl === null) {
            throw ValidationException::withMessages(['prospect' => 'Nome e fonte pubblica obbligatori.']);
        }

        $domain = parse_url($lead->website ?? '', PHP_URL_HOST);
        $domain = is_string($domain) ? $domain : null;

        return [
            'website' => $lead->website,
            'source_url' => $lead->sourceUrl,
            'domain' => $domain,
        ];
    }
}
