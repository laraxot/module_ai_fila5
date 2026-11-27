<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Spatie\QueueableAction\QueueableAction;

/** Compatibility adapter; scoring is delegated to the canonical audit DTO. */
final class ScoreProspectAction
{
    use QueueableAction;

    /**
     * @param  list<array{code?: mixed, status?: mixed, score?: mixed}>  $signals
     * @return array{score: int, reasons: list<string>}
     */
    public function execute(?string $website, array $signals): array
    {
        $score = $website === null ? 0 : 10;
        $reasons = $website === null ? [] : ['Sito aziendale dichiarato nella fonte'];
        foreach ($signals as $signal) {
            if (is_string($signal['code'] ?? null) && ($signal['status'] ?? null) === 'observed_hint') {
                $reasons[] = $signal['code'].' (segnale da verificare, non prova conclusiva)';
            }
        }

        return ['score' => min(100, $score + array_sum(array_map(static fn (array $s): int => is_numeric($s['score'] ?? null) ? (int) $s['score'] : 0, $signals))), 'reasons' => array_values(array_unique($reasons))];
    }
}
