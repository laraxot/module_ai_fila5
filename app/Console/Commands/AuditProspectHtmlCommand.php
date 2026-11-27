<?php

declare(strict_types=1);

namespace Modules\AI\Console\Commands;

use Illuminate\Console\Command;
use Modules\AI\Actions\Prospecting\ExtractProspectSignalsAction;
use Modules\AI\Actions\Prospecting\NormalizeProspectCandidateAction;
use Modules\AI\Actions\Prospecting\ScoreProspectAction;
use Throwable;
use function Safe\filesize;
use function Safe\file_get_contents;

/**
 * Inspect an already-obtained HTML page without HTTP, LLM, database or outreach.
 * The operator is responsible for source provenance and permission to use the file.
 */
final class AuditProspectHtmlCommand extends Command
{
    protected $signature = 'ai:prospects:audit-html
        {source_url : Public source URL (provenance only; never fetched)}
        {file : Local HTML file already acquired with permission}
        {--website= : Declared business website for scoring metadata}';

    protected $description = 'Offline business website hints with evidence; no HTTP, database or sending';

    public function handle(
        NormalizeProspectCandidateAction $normalize,
        ExtractProspectSignalsAction $extract,
        ScoreProspectAction $score,
    ): int {
        $sourceUrl = (string) $this->argument('source_url');
        $file = (string) $this->argument('file');
        $website = $this->option('website');

        if (! is_file($file) || ! is_readable($file)) {
            $this->error('HTML file missing or unreadable.');

            return self::FAILURE;
        }

        $size = filesize($file);
        if ($size > 524288) {
            $this->error('HTML file must not exceed 512 KiB.');

            return self::FAILURE;
        }

        try {
            $normalized = $normalize->execute('offline-cli', [
                'name' => 'Business under review',
                'source_url' => $sourceUrl,
                'website' => is_string($website) && $website !== '' ? $website : null,
            ]);
            $html = file_get_contents($file);
            $signals = $extract->execute($html, $normalized['source_url']);
            /** @see \Modules\AI\Actions\Prospecting\ScoreProspectAction::execute() accepts only its reduced signal shape. */
            $ranking = $score->execute($normalized['website'], array_map(
                static fn (array $signal): array => [
                    'code' => $signal['code'],
                    'status' => $signal['status'],
                ],
                $signals,
            ));
            $this->line((string) json_encode([
                'source_url' => $normalized['source_url'],
                'signals' => $signals,
                'ranking' => $ranking,
                'network_requests' => 0,
                'send_permitted' => false,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Offline audit refused: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
