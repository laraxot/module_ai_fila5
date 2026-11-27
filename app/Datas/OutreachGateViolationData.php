<?php

declare(strict_types=1);

namespace Modules\AI\Datas;

use Modules\AI\Enums\OutreachGateViolationEnum;
use Spatie\LaravelData\Data;

class OutreachGateViolationData extends Data
{
    /** @param array<string, bool|float|int|string> $context dettagli per l'UI (misure, limiti, campo coinvolto) */
    public function __construct(
        public OutreachGateViolationEnum $code,
        public array $context = [],
    ) {}
}
