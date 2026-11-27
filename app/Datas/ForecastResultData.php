<?php

declare(strict_types=1);

namespace Modules\AI\Datas;

use Spatie\LaravelData\Data;

/**
 * Previsione del mese, tutti gli importi in centesimi interi della stessa `currency`.
 *
 * commit = deal in contract_sent · likely = valore pesato per probabilita' · best = tutti gli aperti.
 * `dealCount` = deal aperti con close date nel mese (quelli che compongono best).
 */
final class ForecastResultData extends Data
{
    public function __construct(
        public int $commitCents,
        public int $likelyCents,
        public int $bestCents,
        public string $currency,
        public int $dealCount,
    ) {}
}
