<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\AI\Models\AiProspect;
use Spatie\QueueableAction\QueueableAction;

/**
 * Aggiorna lo stato di una prospect basandosi sui segnali calcolati.
 *
 * Path di stato: NEW -> QUALIFIED -> CONTACTED -> COLD_LOST / WON
 *
 * ECC: only automatic transition is NEW -> QUALIFIED based on signal confidence.
 * All other transitions (QUALIFIED -> CONTACTED, QUALIFIED -> COLD_LOST) must be
 * performed manually via explicit human action — no automatic outreach.
 *
 * @see docs/adr/0004-phpstan-zero-errori.md — `AiProspectStatusEnum` nei confronti di stato
 *      e `@param int` espliciti: nessun `mixed` sulle relazioni Eloquent
 */
class UpdateProspectStatusAction
{
    use QueueableAction;

    /**
     * Aggiorna stato prospect dai segnali associati.
     *
     * Only automatic transition: NEW -> QUALIFIED if confidence >= minConfidence.
     * All other status changes must be triggered manually.
     *
     * @param  int  $minConfidence  minimo confidenza per passare da NEW a QUALIFIED
     * @param  int  $minScore  (unused — reserved for manual QUALIFIED transitions)
     */
    public function execute(AiProspect $prospect, int $minConfidence = 30, int $minScore = 50): void
    {
        $latestSignal = $prospect->signals()->latest('source_at')->first();

        if ($latestSignal === null) {
            return;
        }

        $newStatus = match (true) {
            $prospect->status === AiProspectStatusEnum::NEW && $latestSignal->confidence >= $minConfidence => AiProspectStatusEnum::QUALIFIED,
            default => $prospect->status,
        };

        if ($newStatus !== $prospect->status) {
            $prospect->status = $newStatus;
            $prospect->save();
        }
    }
}
