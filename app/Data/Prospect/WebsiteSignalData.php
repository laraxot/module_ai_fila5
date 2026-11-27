<?php

declare(strict_types=1);

namespace Modules\AI\Data\Prospect;

/**
 * Observation from an examined public page.
 *
 * polarity:
 * - gap: possible opportunity (used in lead score)
 * - strength: capability observed (never scored as opportunity)
 * - measured: timing/security observation from transport or parse
 *
 * confidence: hint | measured | error
 * Absence of a pattern is never proof the business lacks the capability.
 */
final readonly class WebsiteSignalData
{
    public function __construct(
        public string $key,
        public int $score,
        public string $why,
        public string $confidence = 'hint',
        public ?string $evidence = null,
        public string $category = 'general',
        public string $polarity = 'gap',
    ) {}

    /** @return array{key: string, score: int, why: string, confidence: string, evidence: string|null, category: string, polarity: string} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'score' => $this->score,
            'why' => $this->why,
            'confidence' => $this->confidence,
            'evidence' => $this->evidence,
            'category' => $this->category,
            'polarity' => $this->polarity,
        ];
    }
}
