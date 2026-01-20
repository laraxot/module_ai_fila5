<?php

declare(strict_types=1);

namespace Modules\AI\Providers;

// ---- bases --
use Modules\Xot\Providers\XotBaseServiceProvider;

class AIServiceProvider extends XotBaseServiceProvider
{
    public string $name = 'AI'; // lower del nome

    protected string $module_dir = __DIR__;

    protected string $module_ns = __NAMESPACE__;
}
