<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Spatie\QueueableAction\QueueableAction;

/**
 * Explainable deterministic ranking. The host can override weights per product/ICP.
 */
final class ScoreProspectAction
{
    use QueueableAction;

    /**
     * @param list<array<string, mixed>> $signals
     * @return array{score: int, reasons: list<string>}
     */
    public function execute(?string $website, array $signals): array
    {
        $score = $website === null ? 0 : 10;
        $reasons = $website === null ? [] : ['Sito aziendale dichiarato nella fonte'];

        /** @var array<string, int> $weights */
        $weights = config('ai.prospecting.signal_weights', [
            'manual_booking_only' => 35,
            'manual_order_only' => 30,
            'catalogue_link' => 10,
        ]);

        $counted = [];
        foreach ($signals as $signal) {
            $code = $signal['code'] ?? null;
            $status = $signal['status'] ?? null;
            $confidence = $signal['confidence'] ?? 0;
            if (! is_string($code) || $status !== 'observed_hint' || ! is_numeric($confidence)
                || (float) $confidence < 0.6 || isset($counted[$code])) {
                continue;
            }

            $weight = $weights[$code] ?? 0;
            if ($weight <= 0) {
                continue;
            }

            $counted[$code] = true;
            $score += min(50, $weight);
            $reasons[] = $code.' (segnale da verificare, non prova conclusiva)';
        }

        return ['score' => min(100, $score), 'reasons' => $reasons];
    }
}
