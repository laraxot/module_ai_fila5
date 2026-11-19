<?php

declare(strict_types=1);

namespace Modules\AI\Providers;

// ---- bases --
use Modules\AI\Adapters\AiActionHandlerRegistry;
use Modules\AI\Adapters\AssistantHandoffHandler;
use Modules\Xot\Providers\XotBaseServiceProvider;

class AIServiceProvider extends XotBaseServiceProvider
{
    public string $name = 'AI'; // lower del nome

    public function register(): void
    {
        parent::register();

        // Handler di base del modulo; i moduli di dominio registrano i propri (e possono
        // sovrascrivere questi registrando lo stesso tipo).
        $this->app->singleton(
            AiActionHandlerRegistry::class,
            static fn (): AiActionHandlerRegistry => (new AiActionHandlerRegistry)
                ->register(new AssistantHandoffHandler),
        );
    }
}
