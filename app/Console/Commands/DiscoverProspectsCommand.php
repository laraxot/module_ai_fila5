<?php

declare(strict_types=1);

namespace Modules\AI\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Modules\AI\Actions\Prospecting\DiscoverProspectsInAreaAction;
use Modules\AI\Exceptions\AiRequestException;
use Throwable;

/**
 * Experimental provider search. Explicit opt-in, dry-run by default, no website crawling.
 */
final class DiscoverProspectsCommand extends Command
{
    protected $signature = 'ai:prospects:discover
        {workspace : Authorized workspace identifier}
        {area : City or neighborhood}
        {--limit=200 : Maximum candidates, up to 250}
        {--apply : Save candidates after manual review (default is dry-run)}
        {--allow-production : Explicitly permit production writes if also enabled in host config}
        {--research=0 : Disabled until the safe HTTP transport is reviewed}';

    protected $description = 'Provider-approved discovery; dry-run only unless explicitly enabled';

    public function handle(DiscoverProspectsInAreaAction $discover): int
    {
        $workspace = (string) $this->argument('workspace');
        $area = (string) $this->argument('area');
        $apply = $this->option('apply') === true;
        $research = $this->option('research');

        if (is_numeric($research) && (int) $research > 0) {
            $this->error('Live website fetch disabled pending #33 SSRF review. Use ai:prospects:audit-html offline.');

            return self::FAILURE;
        }
        if ($apply && app()->environment('production') && $this->option('allow-production') !== true) {
            $this->error('Production import requires --allow-production and host authorization.');

            return self::FAILURE;
        }

        try {
            $limit = $this->option('limit');
            $result = $discover->execute($workspace, $area, is_numeric($limit) ? (int) $limit : 200, $apply);
            $count = $result['imported'];
            $this->info(sprintf(
                '%s: %d aziende individuate, %s.',
                $result['area'],
                $result['found'],
                $apply ? 'import applicato ('.json_encode($count).')' : 'DRY RUN: nessuna modifica',
            ));
            $this->line('Dati © OpenStreetMap contributors (ODbL). Nessuna comunicazione inviata.');

            return self::SUCCESS;
        } catch (AiRequestException|ValidationException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('Discovery refused: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
