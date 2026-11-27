<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

/**
 * Indirizzi IP di un host (separato per poterlo sostituire nei test, dove il DNS non c'e').
 */
class ResolveHostAction
{
    /** @return list<string> */
    public function execute(string $host): array
    {
        $ips = gethostbynamel($host);

        return $ips === false ? [] : $ips;
    }
}
