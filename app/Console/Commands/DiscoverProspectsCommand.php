<?php

declare(strict_types=1);

namespace Modules\AI\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Modules\AI\Actions\Prospecting\DiscoverProspectsInAreaAction;
use Modules\AI\Actions\Prospecting\ResearchProspectWebsiteAction;
use Modules\AI\Exceptions\AiRequestException;
use Modules\AI\Models\AiProspect;

/**
 * php artisan ai:prospects:discover restaurant-sales "Treviso" --research=20
 *
 * Trova attivita' su OpenStreetMap, le importa nel workspace e (opzionale) ne legge i siti in modo sicuro.
 * Non contatta nessuno. Richiede ai.prospecting.enabled = true.
 */
class DiscoverProspectsCommand extends Command
{
    /** @var string */
    protected $signature = 'ai:prospects:discover {workspace} {area : citta\' o zona} {--limit=200} {--research=0 : quanti siti leggere dopo l\'import}';

    /** @var string */
    protected $description = 'Ricerca clienti: attivita\' da OpenStreetMap nel workspace, poi lettura sicura dei siti (non contatta nessuno)';

    public function handle(DiscoverProspectsInAreaAction $discover, ResearchProspectWebsiteAction $research): int
    {
        $workspace = $this->argument('workspace');
        $area = $this->argument('area');
        if (! \is_string($workspace) || ! \is_string($area)) {
            return self::INVALID;
        }
        $limit = $this->option('limit');
        $toResearch = $this->option('research');

        try {
            $result = $discover->execute($workspace, $area, is_numeric($limit) ? (int) $limit : 200);
        } catch (AiRequestException|ValidationException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info(\sprintf('%s: %d attivita\' trovate e importate nel workspace "%s".', $result['area'], $result['found'], $workspace));

        $pending = AiProspect::query()->where('workspace', $workspace)->where('is_suppressed', false)
            ->whereNull('researched_at')->whereNotNull('website')
            ->limit(is_numeric($toResearch) ? max(0, (int) $toResearch) : 0)->get();
        foreach ($pending as $candidate) {
            $id = $candidate->id;
            try {
                $prospect = $research->execute($workspace, $id);
                $this->line(\sprintf(' - %s: punteggio %d', $prospect->name, $prospect->score));
            } catch (ValidationException $e) {
                $this->warn(' - #'.$id.': '.$e->getMessage());
            }
        }

        $this->line('Dati © OpenStreetMap contributors (ODbL).');

        return self::SUCCESS;
    }
}
