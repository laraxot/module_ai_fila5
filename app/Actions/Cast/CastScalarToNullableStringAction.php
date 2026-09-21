<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Cast;

use Spatie\QueueableAction\QueueableAction;

final class CastScalarToNullableStringAction
{
    use QueueableAction;

    /**
     * @param  mixed  $value  Any raw payload value; non-scalars become null
     */
    public function execute(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
