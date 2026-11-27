<?php

declare(strict_types=1);

namespace Modules\AI\Console\Commands;

use Illuminate\Console\Command;
use Modules\AI\Actions\ImportProspectsAction;
use Throwable;
use function Safe\fclose;
use function Safe\fgetcsv;
use function Safe\fopen;

/**
 * Reviewed file import only. No crawling, sending, paid API or automatic job.
 */
final class ImportProspectsCommand extends Command
{
    protected $signature = 'ai:prospects:import
        {workspace : Authorized workspace identifier}
        {file : Local CSV or Overpass JSON file}
        {--apply : Save validated businesses (default is dry-run)}
        {--allow-production : Explicitly allow writes in a production environment}';

    protected $description = 'Validate and optionally import public business prospects for one workspace';

    public function handle(
        ImportProspectsAction $import,
    ): int {
        $workspace = (string) $this->argument('workspace');
        $path = (string) $this->argument('file');
        $apply = $this->option('apply') === true;

        if ($apply && ! config('ai.prospecting.enabled', false)) {
            $this->error('Prospecting disabled: set AI_PROSPECTING_ENABLED=true after authorization.');

            return self::FAILURE;
        }
        if ($apply && app()->environment('production') && $this->option('allow-production') !== true) {
            $this->error('Production import blocked without --allow-production.');

            return self::FAILURE;
        }
        if (! is_file($path) || ! is_readable($path)) {
            $this->error('Input file not found or unreadable.');

            return self::FAILURE;
        }

        try {
            $rows = $this->readCsv($path);
            $created = 0;
            $updated = 0;
            $suppressed = 0;
            $validated = 0;
            // Reject an invalid file before writing any of its batches.
            foreach ($rows as $row) {
                if (! is_string($row['domain'] ?? null) || trim($row['domain']) === '') {
                    throw new \InvalidArgumentException('Each row requires a domain.');
                }
                $validated++;
            }
            foreach (array_chunk($rows, 250) as $chunk) {
                if (! $apply) {
                    continue;
                }
                $created += $import->execute($chunk, $workspace, 'csv');
            }
            $this->info($apply
                ? "Import complete: {$created} created, {$updated} existing, {$suppressed} suppressed."
                : "DRY RUN: {$validated} businesses validated; nothing saved.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Import refused: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    /** @return list<array<string, mixed>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('CSV could not be opened.');
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new \InvalidArgumentException('CSV requires a header row.');
            }
            $headers = array_map(static fn (string|null $header): string => $header ?? '', $headers);
            if (! in_array('name', $headers, true) || ! in_array('domain', $headers, true)) {
                throw new \InvalidArgumentException('CSV requires name and domain headers.');
            }

            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if (count($values) !== count($headers)) {
                    throw new \InvalidArgumentException('CSV column count differs from the header.');
                }
                $row = array_combine($headers, array_map(static fn (mixed $value): string => (string) $value, $values));
                $rows[] = $row;
                if (count($rows) > 10000) {
                    throw new \InvalidArgumentException('Maximum 10,000 companies per import.');
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

}
