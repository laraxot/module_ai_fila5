<?php

declare(strict_types=1);

namespace Modules\AI\Contracts;

interface SentimentAnalyzer
{
    /**
     * Analizza il sentimento del testo.
     *
     * @return array{label: string, score: int|float, warning: string}
     */
    public function analyze(string $text): array;
}
