<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Illuminate\Support\Carbon;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectSignal;
use Spatie\QueueableAction\QueueableAction;

/**
 * Calcolo deterministico di segnali e prove per una prospect.
 *
 * Fonte: determinata dai dati di import (CSV, OSM, listing pubblico, segnali da
 * sito web, email verificata). Nessuna deduzione da pagine non esaminate.
 * Qualità sorgente (high|medium|low) e confidenza (0-100) esplicite e verificabili.
 * Score (0-100) con breakdown per campo spiegabile.
 *
 * @see docs/adr/0004-phpstan-zero-errori.md — helper `stringOrNull`/`intOrNull`/`arrayOrEmpty`
 *      fanno narrowing reale dei valori `mixed` da `$sourceData` invece di cast
 */
class ComputeProspectSignalsAction
{
    use QueueableAction;

    /**
     * Calcola segnali per una prospect esistente.
     *
     * @param  array<string, mixed>  $sourceData
     */
    public function execute(AiProspect $prospect, array $sourceData): AiProspectSignal
    {
        $source = $this->stringOrNull($sourceData['source'] ?? null) ?? 'website_signals';
        $sourceUrl = $this->stringOrNull($sourceData['source_url'] ?? null);
        $qualitySource = $this->stringOrNull($sourceData['quality_source'] ?? null) ?? 'low';
        $confidence = $this->intOrNull($sourceData['confidence'] ?? null) ?? 0;
        $score = $this->intOrNull($sourceData['score'] ?? null) ?? 0;
        $breakdown = $this->arrayOrEmpty($sourceData['breakdown'] ?? null);

        $signal = new AiProspectSignal();
        $signal->fill([
            'ai_prospect_id' => $prospect->getKey(),
            'source' => $source,
            'source_url' => $sourceUrl,
            'source_at' => Carbon::now(),
            'quality_source' => $qualitySource,
            'confidence' => $confidence,
            'score' => $score,
            'breakdown' => $breakdown,
            'note' => $this->stringOrNull($sourceData['note'] ?? null),
        ]);
        $signal->save();

        return $signal;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_int($value) ? $value : null;
    }

    /** @return array<string, mixed> */
    private function arrayOrEmpty(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            $result[(string) $key] = $item;
        }

        return $result;
    }
}
