<?php

declare(strict_types=1);

namespace Modules\AI\Providers;

// ---- bases --
use Modules\AI\Support\AiActionHandlerRegistry;
use Modules\Xot\Providers\XotBaseServiceProvider;

class AIServiceProvider extends XotBaseServiceProvider
{
    public string $name = 'AI'; // lower del nome

<<<<<<< HEAD
   public function register(): void
=======
    public function register(): void
>>>>>>> laraxot/dev
    {
        parent::register();

        $this->app->singleton(AiActionHandlerRegistry::class);
    }
}
