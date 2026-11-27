<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospect;

use Modules\AI\Data\Prospect\ProspectLeadData;
use Spatie\QueueableAction\QueueableAction;

/** Do-not-contact / suppression list (Closer stop rules). */
final class ApplyProspectSuppressionAction
{
    use QueueableAction;

    /**
     * @param list<string> $suppressedEmails lowercased emails
     */
    public function isSuppressed(ProspectLeadData $lead, array $suppressedEmails = []): bool
    {
        if ($lead->doNotContact) {
            return true;
        }

        $email = $lead->email;
        if ($email === null || $email === '') {
            return false;
        }

        $set = array_map(static fn (string $e): string => strtolower(trim($e)), $suppressedEmails);

        return in_array(strtolower($email), $set, true);
    }

    /**
     * @param list<ProspectLeadData> $leads
     * @param list<string> $suppressedEmails
     * @return list<ProspectLeadData>
     */
    public function filter(array $leads, array $suppressedEmails = []): array
    {
        return array_values(array_filter(
            $leads,
            fn (ProspectLeadData $l): bool => ! $this->isSuppressed($l, $suppressedEmails)
        ));
    }
}
