<?php

declare(strict_types=1);

namespace Modules\AI\Console\Commands;

use Illuminate\Console\Command;

/**
 * The former live-discovery implementation duplicated the prospecting pipeline
 * and wrote a schema that no longer exists. Imports are the canonical entrypoint.
 */
final class DiscoverProspectsCommand extends Command
{
    protected $signature = 'ai:prospects:discover {workspace} {area}';

    protected $description = 'Use ai:prospects:import with canonical AiProspect rows';

    public function handle(): int
    {
        $this->error('Live discovery was removed; use ai:prospects:import.');

        return self::FAILURE;
    }
}
