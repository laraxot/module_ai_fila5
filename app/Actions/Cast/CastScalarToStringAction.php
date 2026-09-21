<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Cast;

use Spatie\QueueableAction\QueueableAction;

final class CastScalarToStringAction
{
    use QueueableAction;

    /**
     * @param  mixed  $value  Any raw payload value; non-scalars fall back to $default
     */
    public function execute(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }
}
