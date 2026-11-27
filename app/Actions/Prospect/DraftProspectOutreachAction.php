<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospect;

use InvalidArgumentException;
use Modules\AI\Data\Prospect\ProspectLeadData;
use Spatie\QueueableAction\QueueableAction;
use function Safe\preg_match_all;

/**
 * Opening email draft only. Never sends.
 * Requires explicit human approval in the host app (Closer: one yes per message).
 */
final class DraftProspectOutreachAction
{
    use QueueableAction;

    public function __construct(
        private readonly ApplyProspectSuppressionAction $suppression,
    ) {}

    /**
     * @param array{name?: string, company?: string, address?: string, offer?: string} $sender
     * @param list<string> $suppressedEmails
     * @return array{
     *   to: string|null,
     *   subject: string,
     *   body: string,
     *   signal: string,
     *   requires_approval: true,
     *   send_permitted: false,
     *   daily_cap_hint: int,
     *   missing: list<string>
     * }
     */
    public function execute(
        ProspectLeadData $lead,
        array $sender = [],
        ?string $signalKey = null,
        array $suppressedEmails = [],
    ): array {
        if ($this->suppression->isSuppressed($lead, $suppressedEmails)) {
            throw new InvalidArgumentException('Prospect is on the do-not-contact / suppression list.');
        }

        $signal = $signalKey ?? ($lead->signalKeys[0] ?? 'generic');
        $first = $this->firstName($lead->contactName ?? $lead->name);
        $offer = $sender['offer'] ?? '[fill: offer]';
        $me = $sender['name'] ?? '[fill: name]';
        $company = $sender['company'] ?? '[fill: company]';
        $address = $sender['address'] ?? '[fill: postal_address]';

        $subject = match ($signal) {
            'no-booking' => 'Quick idea for '.$lead->name,
            'not-mobile' => 'Mobile experience — '.$lead->name,
            default => $lead->name.' — quick note',
        };

        $core = match ($signal) {
            'no-booking' => "Hi {$first},\n\nLooking at {$lead->name}'s public site, I did not see an obvious way to book online. "
                ."Teams we work with often recover after-hours demand with {$offer}.\n\n"
                ."Open to a 15-minute call this week?\n\n{$me}",
            'not-mobile' => "Hi {$first},\n\n{$lead->name}'s public page looks light on mobile signals (viewport). "
                ."If mobile traffic matters, {$offer} may help.\n\nWorth a short call?\n\n{$me}",
            default => "Hi {$first},\n\nI work with teams like {$lead->name} on {$offer}. "
                ."If relevant, happy to share a short overview.\n\n{$me}",
        };

        $body = $core.sprintf(
            "\n\n--\n%s, %s\n%s\nNot interested? Reply \"no thanks\" and I will not email again.",
            $me,
            $company,
            $address
        );

        $matches = [];
        preg_match_all('/\[fill: (\w+)\]/', $body, $matches);
        $missing = [];
        if (isset($matches[1]) && is_array($matches[1])) {
            foreach ($matches[1] as $field) {
                if (is_string($field)) {
                    $missing[] = $field;
                }
            }
        }

        return [
            'to' => $lead->email,
            'subject' => $subject,
            'body' => $body,
            'signal' => $signal,
            'requires_approval' => true,
            'send_permitted' => false,
            'daily_cap_hint' => 50,
            'missing' => array_values(array_unique($missing)),
        ];
    }

    private function firstName(string $who): string
    {
        $p = explode(' ', trim($who))[0] ?? 'there';

        return $p !== '' ? $p : 'there';
    }
}
