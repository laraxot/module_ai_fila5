<?php

declare(strict_types=1);

namespace Modules\AI\Console\Commands;

use Illuminate\Console\Command;
use Modules\AI\Actions\Prospect\DetectWebsiteSignalsAction;

use function Safe\json_encode;

/** @see docs/adr/0004-phpstan-zero-errori.md — `Safe\json_encode` mai `false` silenzioso sull'output JSON */
final class ProspectAuditCommand extends Command
{
    protected $signature = 'ai:prospect-audit
                            {url : Public website URL}
                            {--type=business : Business type hint for detectors}';

    protected $description = 'Audit one public homepage for prospect signals (no send)';

    public function handle(DetectWebsiteSignalsAction $detect): int
    {
        $url = $this->argument('url');
        if (! is_string($url) || $url === '') {
            $this->error('Public website URL is required.');

            return self::FAILURE;
        }

        $type = $this->option('type');
        $type = is_string($type) ? $type : 'business';

        $result = $detect->execute($url, $type);

        $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $result->ok ? self::SUCCESS : self::FAILURE;
    }
}
