<?php

declare(strict_types=1);

namespace Modules\AI\Console\Commands;

use Illuminate\Console\Command;
use Modules\AI\Actions\Prospecting\ImportOsmCandidatesAction;
use Modules\AI\Actions\Prospecting\ImportProspectCandidatesAction;
use Modules\AI\Actions\Prospecting\NormalizeProspectCandidateAction;
use Throwable;

/**
 * Reviewed file import only. No crawling, sending, paid API or automatic job.
 */
final class ImportProspectsCommand extends Command
{
    protected $signature = 'ai:prospects:import
        {workspace : Authorized workspace identifier}
        {file : Local CSV or Overpass JSON file}
        {--osm : Read an exported Overpass JSON file instead of CSV}
        {--apply : Save validated businesses (default is dry-run)}
        {--allow-production : Explicitly allow writes in a production environment}';

    protected $description = 'Validate and optionally import public business prospects for one workspace';

    public function handle(
        NormalizeProspectCandidateAction $normalize,
        ImportProspectCandidatesAction $import,
        ImportOsmCandidatesAction $osm,
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
            $rows = $this->option('osm') === true ? $this->readOsm($path, $osm) : $this->readCsv($path);
            $created = 0;
            $updated = 0;
            $suppressed = 0;
            $validated = 0;
            // Reject an invalid file before writing any of its batches.
            foreach ($rows as $row) {
                $normalize->execute($workspace, $row);
                $validated++;
            }
            foreach (array_chunk($rows, 250) as $chunk) {
                if (! $apply) {
                    continue;
                }
                $result = $import->execute($workspace, $chunk);
                $created += $result['created'];
                $updated += $result['updated'];
                $suppressed += $result['suppressed'];
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
            if (! is_array($headers) || ! in_array('name', $headers, true) || ! in_array('source_url', $headers, true)) {
                throw new \InvalidArgumentException('CSV requires name and source_url headers.');
            }

            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if (count($values) !== count($headers)) {
                    throw new \InvalidArgumentException('CSV column count differs from the header.');
                }
                $row = array_combine($headers, $values);
                if (! is_array($row)) {
                    continue;
                }
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

    /** @return list<array<string, mixed>> */
    private function readOsm(string $path, ImportOsmCandidatesAction $osm): array
    {
        $json = file_get_contents($path);
        if ($json === false || strlen($json) > 10485760) {
            throw new \InvalidArgumentException('OSM file invalid or larger than 10 MiB.');
        }
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Overpass JSON must be an object.');
        }

        return $osm->execute($decoded, 10000);
    }
}
