<?php

declare(strict_types=1);

namespace Modules\AI\Datas;

use Spatie\LaravelData\Data;

/**
 * Soglie del gate di invio. Non esiste (di proposito) un'opzione per spegnere l'approvazione umana.
 */
class OutreachGateLimitsData extends Data
{
    public function __construct(
        public int $maxSubjectChars = 90,
        public int $maxBodyWords = 150,
        public int $maxLinks = 1,
    ) {}
}
