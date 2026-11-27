<?php

declare(strict_types=1);

namespace Modules\AI\Data\Prospect;

/** Hint from a public page (not a proven absence). */
final readonly class WebsiteSignalData
{
    public function __construct(
        public string $key,
        public int $score,
        public string $why,
        public string $confidence = 'hint',
        public ?string $evidence = null,
    ) {}

    /** @return array{key: string, score: int, why: string, confidence: string, evidence: string|null} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'score' => $this->score,
            'why' => $this->why,
            'confidence' => $this->confidence,
            'evidence' => $this->evidence,
        ];
    }
}
