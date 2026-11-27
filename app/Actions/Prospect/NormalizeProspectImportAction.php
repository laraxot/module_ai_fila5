<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospect;

use Modules\AI\Data\Prospect\ProspectLeadData;
use Spatie\QueueableAction\QueueableAction;

/** Idempotent import: normalize rows, drop empties, dedupe by identity. */
final class NormalizeProspectImportAction
{
    use QueueableAction;

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<ProspectLeadData>
     */
    public function execute(array $rows, ?string $defaultSourceUrl = null): array
    {
        $byId = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $lead = ProspectLeadData::fromArray($row);
            if ($lead->name === '') {
                continue;
            }
            if ($defaultSourceUrl !== null && $lead->sourceUrl === null) {
                $lead = ProspectLeadData::fromArray(array_merge($lead->toArray(), [
                    'source_url' => $defaultSourceUrl,
                    'name' => $lead->name,
                    'website' => $lead->website,
                    'who' => $lead->contactName,
                    'email' => $lead->email,
                    'type' => $lead->businessType,
                    'city' => $lead->city,
                    'signals' => $lead->signalKeys,
                ]));
            }

            $byId[$lead->identityKey()] = $lead;
        }

        return array_values($byId);
    }
}
