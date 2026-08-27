<?php

declare(strict_types=1);
use Modules\AI\Tests\TestCase;

/*
 * Bootstrap Pest — modulo AI.
 * Ogni file test dichiara uses(\Modules\AI\Tests\TestCase::class).
 * Vietato RefreshDatabase (dati sacri) e uses()->in() qui.
 */

pest()->extend(TestCase::class)->in(__DIR__.'/Unit', __DIR__.'/Feature');
