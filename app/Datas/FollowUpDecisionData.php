<?php

declare(strict_types=1);

namespace Modules\AI\Datas;

use Carbon\CarbonImmutable;
use Modules\AI\Enums\FollowUpStateEnum;
use Spatie\LaravelData\Data;

/**
 * Esito di `NextFollowUpAction`.
 *
 * `step` (1-based) e `dueAt` valgono solo per `due` e `waiting`; `stopReason` solo per `stopped`.
 * Gli altri campi restano null.
 */
final class FollowUpDecisionData extends Data
{
    /** Codici stabili di `stopReason`, in ordine di precedenza. */
    public const STOP_SUPPRESSED = 'suppressed';

    public const STOP_REPLIED = 'replied';

    public const STOP_MEETING_BOOKED = 'meeting_booked';

    public const STOP_MAX_ATTEMPTS = 'max_attempts';

    public const STOP_MAX_AGE = 'max_age';

    public function __construct(
        public FollowUpStateEnum $state,
        public ?int $step = null,
        public ?CarbonImmutable $dueAt = null,
        public ?string $stopReason = null,
    ) {}

    public static function stopped(string $stopReason): self
    {
        return new self(state: FollowUpStateEnum::STOPPED, stopReason: $stopReason);
    }
}
