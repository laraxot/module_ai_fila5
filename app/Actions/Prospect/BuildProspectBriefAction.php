<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospect;

use Modules\AI\Data\Prospect\ProspectLeadData;
use Modules\AI\Data\Prospect\WebsiteAuditResultData;
use Spatie\QueueableAction\QueueableAction;

/** Researcher-style one-page brief; facts only from audit + lead fields. */
final class BuildProspectBriefAction
{
    use QueueableAction;

    public function __construct(
        private readonly DetectWebsiteSignalsAction $detect,
    ) {}

    /**
     * @return array{
     *   lead: array<string, mixed>,
     *   audit: array<string, mixed>|null,
     *   talking_points: list<string>,
     *   questions: list<string>,
     *   sources: list<string>,
     *   markdown: string
     * }
     */
    public function execute(ProspectLeadData $lead, ?WebsiteAuditResultData $audit = null, bool $fetch = true): array
    {
        if ($audit === null && $fetch && $lead->website) {
            $audit = $this->detect->execute($lead->website, $lead->businessType);
        }

        $talking = [sprintf('Company: %s.', $lead->name)];
        if ($lead->city) {
            $talking[] = sprintf('Location: %s%s.', $lead->city, $lead->country ? ', '.$lead->country : '');
        }
        if ($audit?->title) {
            $talking[] = sprintf('Public title: «%s».', $audit->title);
        }

        $questions = [];
        $sources = array_values(array_filter([$audit?->url, $lead->website, $lead->sourceUrl]));

        foreach ($audit?->signals ?? [] as $signal) {
            $talking[] = sprintf('Signal (%s): %s.', $signal->confidence, $signal->why);
            $questions[] = $this->questionFor($signal->key);
        }

        if ($questions === []) {
            $questions[] = 'What does the buying process look like for tools like ours?';
            $questions[] = 'Who owns the decision for this area?';
        }

        $md = $this->markdown($lead, $talking, $questions, $sources);

        return [
            'lead' => $lead->toArray(),
            'audit' => $audit?->toArray(),
            'talking_points' => $talking,
            'questions' => array_values(array_unique($questions)),
            'sources' => $sources,
            'markdown' => $md,
        ];
    }

    private function questionFor(string $key): string
    {
        return match ($key) {
            'no-booking' => 'How do prospects book time with you today?',
            'not-mobile' => 'How much of your traffic is mobile?',
            'no-schema' => 'Is your public site structured for search engines?',
            'slow' => 'Have you measured site speed on mobile?',
            default => 'What is the main friction in how customers reach you online?',
        };
    }

    /**
     * @param list<string> $talking
     * @param list<string> $questions
     * @param list<string> $sources
     */
    private function markdown(ProspectLeadData $lead, array $talking, array $questions, array $sources): string
    {
        $lines = ['# Brief: '.$lead->name, ''];
        foreach ($talking as $t) {
            $lines[] = '- '.$t;
        }
        $lines[] = '';
        $lines[] = '## Questions';
        foreach ($questions as $q) {
            $lines[] = '- '.$q;
        }
        $lines[] = '';
        $lines[] = '## Sources';
        foreach ($sources as $s) {
            $lines[] = '- '.$s;
        }
        $lines[] = '';
        $lines[] = '_Signals are hints from examined public pages. Do not invent metrics._';

        return implode("\n", $lines);
    }
}
