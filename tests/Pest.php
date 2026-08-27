<?php

declare(strict_types=1);

/*
 * Bootstrap Pest — modulo AI.
 * Ogni file test dichiara uses(\Modules\AI\Tests\TestCase::class).
 * Vietato RefreshDatabase (dati sacri) e uses()->in() qui.
 */

pest()->extend(\Modules\AI\Tests\TestCase::class)->in(__DIR__.'/Unit', __DIR__.'/Feature');
