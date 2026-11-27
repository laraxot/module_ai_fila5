<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospect;

use Modules\AI\Data\Prospect\ProspectLeadData;
use Modules\AI\Data\Prospect\WebsiteAuditResultData;
use Spatie\QueueableAction\QueueableAction;

final class RankProspectLeadsAction
{
    use QueueableAction;

    public function __construct(
        private readonly DetectWebsiteSignalsAction $detect,
        private readonly ApplyProspectSuppressionAction $suppression,
    ) {}

    /**
     * @param list<ProspectLeadData> $leads
     * @param list<string> $suppressedEmails
     * @return list<ProspectLeadData>
     */
    public function execute(array $leads, bool $auditWebsites = true, array $suppressedEmails = []): array
    {
        $out = [];

        foreach ($leads as $lead) {
            if ($this->suppression->isSuppressed($lead, $suppressedEmails)) {
                continue;
            }

            $audit = null;
            if ($auditWebsites && $lead->website) {
                $audit = $this->detect->execute($lead->website, $lead->businessType);
            }

            $out[] = $this->merge($lead, $audit);
        }

        usort(
            $out,
            static fn (ProspectLeadData $a, ProspectLeadData $b): int => $b->score <=> $a->score ?: strcmp($a->name, $b->name)
        );

        return $out;
    }

    private function merge(ProspectLeadData $lead, ?WebsiteAuditResultData $audit): ProspectLeadData
    {
        if ($audit === null) {
            return $lead;
        }

        return new ProspectLeadData(
            name: $lead->name,
            website: $lead->website,
            contactName: $lead->contactName,
            email: $lead->email,
            phone: $lead->phone,
            city: $lead->city,
            country: $lead->country,
            businessType: $lead->businessType,
            sourceUrl: $lead->sourceUrl,
            score: $audit->totalScore(),
            why: $audit->ok ? ($audit->signals[0]->why ?? null) : 'Sito non esaminato; segnali sconosciuti',
            signalKeys: $audit->signalKeys(),
            meta: array_merge($lead->meta, ['audit' => $audit->toArray()]),
            doNotContact: $lead->doNotContact,
        );
    }
}
