<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Cast;

use Spatie\QueueableAction\QueueableAction;

/**
 * Converte valori primitivi in stringhe scalari (risposte API / prediction draft).
 */
final class ScalarCasterAction
{
    use QueueableAction;

    /**
     * @param  mixed  $value  Any raw payload value; non-scalars fall back to $default
     */
    public function execute(mixed $value, string $default = ''): string
    {
        return $this->handle($value, $default);
    }

    /**
     * @param  mixed  $value  Any raw payload value; non-scalars fall back to $default
     */
    public function handle(mixed $value, string $default = ''): string
    {
        if ($value === null) {
            return $default;
        }

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param  mixed  $value  Any raw payload value; non-scalars fall back to $default
     */
    public static function string(mixed $value, string $default = ''): string
    {
        return app(self::class)->execute($value, $default);
    }

    /**
     * @param  mixed  $value  Any raw payload value; non-scalars become null
     */
    public function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $scalar = $this->scalarCheck($value);

        return $scalar === '' ? null : $scalar;
    }

    /**
     * @param  mixed  $value  Expected array of scalars; non-array input returns []
     * @return list<string>
     */
    public function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $tag) {
            $scalar = $this->scalarCheck($tag);
            if ($scalar !== '') {
                $out[] = $scalar;
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $value  Any raw payload value; non-scalars become ''
     */
    private function scalarCheck(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
