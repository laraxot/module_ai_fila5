<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Validation\ValidationException;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectSuppression;
use Spatie\QueueableAction\QueueableAction;

/**
 * Idempotent workspace-scoped import. Never imports people or contacts.
 */
final class ImportProspectCandidatesAction
{
    use QueueableAction;

    public function __construct(private readonly NormalizeProspectCandidateAction $normalize) {}

    /**
     * @param list<array<string, mixed>> $candidates
     * @return array{created: int, updated: int, suppressed: int}
     */
    public function execute(string $workspace, array $candidates): array
    {
        if (count($candidates) > 250) {
            throw ValidationException::withMessages(['candidates' => 'Massimo 250 aziende per batch.']);
        }

        // Validate the whole batch before the first database write.
        $rows = array_map(fn (array $item): array => $this->normalize->execute($workspace, $item), $candidates);
        $created = 0;
        $updated = 0;
        $suppressed = 0;

        foreach ($rows as $row) {
            if (AiProspectSuppression::query()
                ->where('workspace', $row['workspace'])
                ->where('fingerprint', $row['fingerprint'])
                ->exists()) {
                $suppressed++;

                continue;
            }

            $prospect = AiProspect::query()->firstOrCreate(
                ['workspace' => $row['workspace'], 'fingerprint' => $row['fingerprint']],
                [...$row, 'stage' => 'discovered', 'discovered_at' => now()],
            );

            if ($prospect->wasRecentlyCreated) {
                $created++;

                continue;
            }

            // A re-import cannot silently reset a human-reviewed stage or suppression.
            if (! $prospect->is_suppressed) {
                $prospect->fill([
                    'source_provider' => $row['source_provider'],
                    'source_url' => $row['source_url'],
                ]);
                $prospect->save();
            }
            $updated++;
        }

        return compact('created', 'updated', 'suppressed');
    }
}
