<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Validation\ValidationException;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectSuppression;
use Spatie\QueueableAction\QueueableAction;

/**
 * Ingests one supplied HTML page whose host matches the prospect's website.
 * Fetching is the responsibility of a reviewed, SSRF-safe adapter in the host.
 */
final class ResearchProspectAction
{
    use QueueableAction;

    public function __construct(
        private readonly NormalizeProspectCandidateAction $normalize,
        private readonly ExtractProspectSignalsAction $extract,
        private readonly ScoreProspectAction $score,
    ) {}

    public function execute(string $workspace, int $id, string $html, string $sourceUrl): AiProspect
    {
        $prospect = AiProspect::query()->where('workspace', $workspace)->whereKey($id)->first();
        if (! $prospect instanceof AiProspect || $prospect->is_suppressed || $prospect->domain === null
            || AiProspectSuppression::query()->where('workspace', $workspace)->where('fingerprint', $prospect->fingerprint)->exists()) {
            throw ValidationException::withMessages(['id' => 'Prospect non ricercabile in questo workspace.']);
        }

        $source = $this->normalize->execute($workspace, [
            'name' => $prospect->name,
            'source_url' => $sourceUrl,
            'website' => $sourceUrl,
        ]);
        if ($source['domain'] !== $prospect->domain) {
            throw ValidationException::withMessages(['source_url' => 'La fonte non appartiene al dominio del prospect.']);
        }

        $signals = $this->extract->execute($html, $sourceUrl);
        $ranking = $this->score->execute($prospect->website, $signals);
        $prospect->fill([
            'signals' => $signals,
            'score' => $ranking['score'],
            // A fresh audit cannot reset a stage already selected by a human.
            'stage' => in_array($prospect->stage, ['discovered', 'researched'], true) ? 'researched' : $prospect->stage,
            'researched_at' => now(),
        ]);
        $prospect->save();

        return $prospect;
    }
}
