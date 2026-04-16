<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app['config'];

        $config->set('database.default', env('DB_CONNECTION', 'clickhouse'));

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
            'settings'        => [],
            'fix_default_query_builder' => true,
        ]);
    }

    public function boot(): void
    {
        //
    }
}
