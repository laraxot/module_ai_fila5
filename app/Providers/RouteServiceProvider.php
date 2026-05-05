<?php

declare(strict_types=1);

namespace Modules\AI\Providers;

// --- bases ---
use Modules\Xot\Providers\XotBaseRouteServiceProvider;

class RouteServiceProvider extends XotBaseRouteServiceProvider
{

    public string $name = 'AI';
    protected string $moduleNamespace = 'Modules\AI\Http\Controllers';

    protected string $module_dir = __DIR__;

    protected string $module_ns = __NAMESPACE__;
}
