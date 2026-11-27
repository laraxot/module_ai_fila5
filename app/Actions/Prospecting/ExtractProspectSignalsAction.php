<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Modules\AI\Actions\Prospect\DetectWebsiteSignalsAction;
use Spatie\QueueableAction\QueueableAction;

/** Compatibility adapter over the canonical website-audit DTO. */
final class ExtractProspectSignalsAction
{
    use QueueableAction;

    public function __construct(private readonly DetectWebsiteSignalsAction $detect) {}

    /** @return list<array{code: string, status: string, evidence: string, source_url: string, confidence: float}> */
    public function execute(string $html, string $sourceUrl): array
    {
        return array_map(static fn ($signal): array => [
            'code' => $signal->key,
            'status' => $signal->polarity === 'gap' ? 'observed_hint' : $signal->polarity,
            'evidence' => $signal->evidence ?? $signal->why,
            'source_url' => $sourceUrl,
            'confidence' => $signal->confidence === 'measured' ? 1.0 : 0.8,
        ], $this->detect->analyzeHtml($html, context: ['page_url' => $sourceUrl]));
    }
}
