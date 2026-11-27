<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Outreach;

use InvalidArgumentException;
use Modules\AI\Enums\DealStageEnum;
use Spatie\QueueableAction\QueueableAction;

/**
 * Decide se un deal puo' passare da uno stadio all'altro e restituisce lo stadio finale.
 *
 * Regole, nell'ordine:
 *  1. stesso stadio = no-op (anche per won/lost, anche senza motivo);
 *  2. da won/lost non si esce, nemmeno per un umano. Unica eccezione, il win-back esplicito
 *     `lost -> contacted`, ammesso SOLO se `$byHuman` (riattivare un deal perso e' una decisione umana);
 *  3. verso `lost` da uno stadio aperto e' sempre ammesso (umano o automatico) ma richiede un `$lostReason` non vuoto;
 *  4. un umano puo' impostare qualunque stadio aperto o `won`: salti in avanti e ritorni indietro inclusi;
 *  5. un avanzamento automatico va avanti di UN passo alla volta e mai verso stadi human-only.
 *
 * Errori: InvalidArgumentException il cui messaggio e' ESATTAMENTE uno dei codici ERR_* (nessun testo extra).
 *
 * ponytail: `rank()` assume che gli stadi aperti siano una catena lineare; se nasce un ramo, serve una tabella di adiacenza.
 */
final class AdvanceDealStageAction
{
    use QueueableAction;

    public const string ERR_TERMINAL = 'deal_stage_terminal';

    public const string ERR_LOST_REASON_REQUIRED = 'deal_stage_lost_reason_required';

    public const string ERR_HUMAN_ONLY = 'deal_stage_human_only';

    public const string ERR_BACKWARDS = 'deal_stage_backwards';

    public const string ERR_SKIP_REQUIRES_HUMAN = 'deal_stage_skip_requires_human';

    /**
     * @throws InvalidArgumentException con un codice ERR_* come messaggio
     */
    public function execute(DealStageEnum $from, DealStageEnum $to, bool $byHuman, ?string $lostReason = null): DealStageEnum
    {
        if ($from === $to) {
            return $from;
        }

        if (! $from->isOpen()) {
            return $this->winBack($from, $to, $byHuman);
        }

        if ($to === DealStageEnum::LOST) {
            return $this->lose($lostReason);
        }

        if ($byHuman) {
            return $to;
        }

        return $this->advanceAutomatically($from, $to);
    }

    private function winBack(DealStageEnum $from, DealStageEnum $to, bool $byHuman): DealStageEnum
    {
        if ($byHuman && $from === DealStageEnum::LOST && $to === DealStageEnum::CONTACTED) {
            return $to;
        }

        throw new InvalidArgumentException(self::ERR_TERMINAL);
    }

    private function lose(?string $lostReason): DealStageEnum
    {
        // ponytail: trim() ASCII, un motivo fatto di soli spazi Unicode (NBSP) passerebbe; \p{Z} se serve.
        if (trim($lostReason ?? '') === '') {
            throw new InvalidArgumentException(self::ERR_LOST_REASON_REQUIRED);
        }

        return DealStageEnum::LOST;
    }

    private function advanceAutomatically(DealStageEnum $from, DealStageEnum $to): DealStageEnum
    {
        if ($to->isHumanOnly()) {
            throw new InvalidArgumentException(self::ERR_HUMAN_ONLY);
        }

        if ($to->rank() < $from->rank()) {
            throw new InvalidArgumentException(self::ERR_BACKWARDS);
        }

        if ($to->rank() > $from->rank() + 1) {
            throw new InvalidArgumentException(self::ERR_SKIP_REQUIRES_HUMAN);
        }

        return $to;
    }
}
