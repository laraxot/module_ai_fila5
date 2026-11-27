<?php

declare(strict_types=1);

namespace Modules\AI\Datas;

use Modules\AI\Enums\OutreachGateViolationEnum;
use Spatie\LaravelData\Data;

class OutreachGateResultData extends Data
{
    /** @param list<OutreachGateViolationData> $violations */
    public function __construct(
        public bool $allowed,
        public array $violations = [],
    ) {}

    /** @param list<OutreachGateViolationData> $violations */
    public static function fromViolations(array $violations): self
    {
        return new self(allowed: $violations === [], violations: $violations);
    }

    public function has(OutreachGateViolationEnum $code): bool
    {
        foreach ($this->violations as $violation) {
            if ($violation->code === $code) {
                return true;
            }
        }

        return false;
    }
}
