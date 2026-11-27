<?php

declare(strict_types=1);

namespace Modules\AI\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Modules\AI\Providers\AIServiceProvider;
use Modules\Xot\Tests\XotBaseTestCase;
use Modules\Xot\Tests\XotModuleSchema;

/**
 * Base test case for AI module.
 *
 * Unit tests here do not require database migrations.
 * Vietato RefreshDatabase / DatabaseMigrations (dati sacri).
 */
abstract class TestCase extends XotBaseTestCase
{
    /**
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders(Application $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AIServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openai.api_key' => 'test-openai-key',
            'services.openai.model' => 'gpt-3.5-turbo-instruct',
        ]);
    }

    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        /** @var array<string, array<string, mixed>> $connections */
        $connections = (array) config('database.connections', []);
        foreach (array_keys($connections) as $connection) {
            // `.env.testing` may select MariaDB; worker tests must never share it.
            config()->set("database.connections.{$connection}.driver", 'sqlite');
            config()->set("database.connections.{$connection}.url", null);
            config()->set("database.connections.{$connection}.prefix", '');
            DB::purge($connection);
        }

        XotModuleSchema::isolate('ai');
        XotModuleSchema::ensure('AI');
    }
}
