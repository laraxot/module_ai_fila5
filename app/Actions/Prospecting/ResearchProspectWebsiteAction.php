<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Validation\ValidationException;
use Modules\AI\Models\AiProspect;
use Spatie\QueueableAction\QueueableAction;

/**
 * Legge davvero il sito di un prospect e lo passa a ResearchProspectAction (segnali + punteggio).
 *
 * La lettura passa da FetchPublicPageAction, SPERIMENTALE e disabilitata per default.
 * La validazione attuale di URL e DNS non e' ancora SSRF-safe: vedere #33 e docs/prospecting.md. Se la lettura fallisce non si assegna nessun punteggio (#33): un sito che non risponde non e'
 * un'opportunita' dimostrata, e il prospect resta com'era.
 */
final class ResearchProspectWebsiteAction
{
    use QueueableAction;

    public function __construct(
        private readonly FetchPublicPageAction $fetch,
        private readonly ResearchProspectAction $research,
    ) {}

    public function execute(string $workspace, int $id): AiProspect
    {
        if (! config()->boolean('ai.prospecting.enabled', false)) {
            throw ValidationException::withMessages(['id' => 'Ricerca clienti spenta (ai.prospecting.enabled = false).']);
        }

        $prospect = AiProspect::query()->where('workspace', $workspace)->whereKey($id)->first();
        if (! $prospect instanceof AiProspect || $prospect->website === null || $prospect->website === '') {
            throw ValidationException::withMessages(['id' => 'Prospect senza sito in questo workspace.']);
        }

        $page = $this->fetch->execute($prospect->website);
        if (! $page['ok']) {
            throw ValidationException::withMessages(['website' => 'Sito non letto: '.($page['error'] ?? 'errore').'. Nessun punteggio assegnato.']);
        }

        return $this->research->execute($workspace, $id, $page['html'], $page['url']);
    }
}
