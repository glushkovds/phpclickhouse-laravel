<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PhpClickHouseLaravel\ClickhouseServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    private static bool $migrated = false;

    protected function getPackageProviders($app): array
    {
        return [
            ClickhouseServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$migrated) {
            Artisan::call('migrate', ['--path' => 'tests/migrations', '--realpath' => false]);
            self::$migrated = true;
        }
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('database.default', 'clickhouse');

        $config->set('database.connections.clickhouse', [
            'driver'          => 'clickhouse',
            'host'            => env('CLICKHOUSE_HOST', '127.0.0.1'),
            'port'            => env('CLICKHOUSE_PORT', '18123'),
            'database'        => env('CLICKHOUSE_DATABASE', 'default'),
            'username'        => env('CLICKHOUSE_USERNAME', 'default'),
            'password'        => env('CLICKHOUSE_PASSWORD', ''),
            'timeout_connect' => 2,
            'timeout_query'   => 2,
            'https'           => false,
            'retries'         => 0,
            'settings'        => ['max_partitions_per_insert_block' => 300],
            'fix_default_query_builder' => true,
        ]);

        $config->set('database.connections.clickhouse2', [
            'driver'   => 'clickhouse',
            'host'     => env('CLICKHOUSE2_HOST', '127.0.0.1'),
            'port'     => env('CLICKHOUSE2_PORT', '18124'),
            'database' => 'default',
            'username' => 'default',
            'password' => '',
            'timeout_connect' => 2,
            'timeout_query'   => 2,
            'https'    => false,
            'retries'  => 0,
        ]);

        $config->set('database.connections.clickhouse-cluster', [
            'driver'  => 'clickhouse',
            'cluster' => [
                ['host' => env('CLICKHOUSE_HOST', '127.0.0.1'),  'port' => env('CLICKHOUSE_PORT', '18123')],
                ['host' => env('CLICKHOUSE2_HOST', '127.0.0.1'), 'port' => env('CLICKHOUSE2_PORT', '18124')],
            ],
            'cluster_name'   => 'company_cluster',
            'database'       => 'default',
            'username'       => 'default',
            'password'       => '',
            'timeout_connect'=> 2,
            'timeout_query'  => 2,
            'https'          => false,
            'retries'        => 0,
        ]);

        $config->set('database.connections.problem-clickhouse-cluster', [
            'driver'  => 'clickhouse',
            'cluster' => [
                ['host' => 'clickhouse-does-not-exist', 'port' => '8123'],
                ['host' => env('CLICKHOUSE2_HOST', '127.0.0.1'), 'port' => env('CLICKHOUSE2_PORT', '18124')],
            ],
            'database'       => 'default',
            'username'       => 'default',
            'password'       => '',
            'timeout_connect'=> 2,
            'timeout_query'  => 2,
            'https'          => false,
            'retries'        => 0,
        ]);

        $app->afterResolving('migrator', function ($migrator) {
            $migrator->path(__DIR__ . '/migrations');
        });
    }
}
