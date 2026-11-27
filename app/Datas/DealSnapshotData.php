<?php

declare(strict_types=1);

namespace Modules\AI\Datas;

use Carbon\CarbonImmutable;
use Modules\AI\Enums\DealStageEnum;
use Spatie\LaravelData\Data;

/**
 * Fotografia di un deal, quanto basta al forecast: nessun id, nessun contatto.
 *
 * `valueCents` = valore del PRIMO ANNO in centesimi interi (mai float per i soldi).
 */
final class DealSnapshotData extends Data
{
    public function __construct(
        public DealStageEnum $stage,
        public int $valueCents,
        public ?CarbonImmutable $closeDate,
        public string $currency = 'EUR',
    ) {}
}
