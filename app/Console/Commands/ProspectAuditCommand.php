<?php

declare(strict_types=1);

namespace Modules\AI\Console\Commands;

use Illuminate\Console\Command;
use Modules\AI\Actions\Prospect\DetectWebsiteSignalsAction;

final class ProspectAuditCommand extends Command
{
    protected $signature = 'ai:prospect-audit
                            {url : Public website URL}
                            {--type=business : Business type hint for detectors}';

    protected $description = 'Audit one public homepage for prospect signals (no send)';

    public function handle(DetectWebsiteSignalsAction $detect): int
    {
        $url = (string) $this->argument('url');
        $type = (string) $this->option('type');
        $result = $detect->execute($url, $type);

        $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $result->ok ? self::SUCCESS : self::FAILURE;
    }
}
