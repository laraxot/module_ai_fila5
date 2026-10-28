<?php

declare(strict_types=1);

namespace Modules\AI\Providers;

// ---- bases --
<<<<<<< HEAD
use Modules\AI\Adapters\AiActionHandlerRegistry;
=======
>>>>>>> fa79a29 (.)
use Modules\Xot\Providers\XotBaseServiceProvider;

class AIServiceProvider extends XotBaseServiceProvider
{
    public string $name = 'AI'; // lower del nome
<<<<<<< HEAD

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AiActionHandlerRegistry::class);
    }
=======
>>>>>>> fa79a29 (.)
}
