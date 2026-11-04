<?php

declare(strict_types=1);

namespace Modules\AI\Datas;

use Modules\Xot\Datas\Concerns\MakesFromTenantConfig;
use Spatie\LaravelData\Data;

/** Tenant configuration reserved for future AI settings. */
class AIConfigData extends Data
{
    use MakesFromTenantConfig;

    public bool $enabled = true;
}
