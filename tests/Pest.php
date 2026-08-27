<?php

declare(strict_types=1);

/*
 * Bootstrap Pest — modulo AI.
 * Ogni file test dichiara da sé uses(\Modules\AI\Tests\TestCase::class).
 * Vietato RefreshDatabase (dati sacri) e pest()->extend()->in() / uses()->in() qui:
 * toccano le classi @internal Pest\Configuration e Pest\PendingCalls\UsesCall
 * (PHPStan method.internalClass) e sono comunque ridondanti visto che ogni file
 * dichiara già il proprio binding.
 */
