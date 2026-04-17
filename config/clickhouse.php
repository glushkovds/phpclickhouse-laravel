<?php

/*
|--------------------------------------------------------------------------
| ClickHouse connections
|--------------------------------------------------------------------------
|
| Each top-level key defines a ClickHouse connection that the service
| provider merges into config('database.connections.<name>'). Values you
| set in your own config/database.php always win over the defaults here.
|
| Publish this file with:
|   php artisan vendor:publish --tag=clickhouse-config
|
| Add as many connections as you need. For a cluster, replace the `host`
| and `port` pair with a `cluster` array of nodes (see README).
|
*/

return [

    'clickhouse' => [
        'driver'          => 'clickhouse',
        'host'            => env('CLICKHOUSE_HOST', '127.0.0.1'),
        'port'            => env('CLICKHOUSE_PORT', '8123'),
        'database'        => env('CLICKHOUSE_DATABASE', 'default'),
        'username'        => env('CLICKHOUSE_USERNAME', 'default'),
        'password'        => env('CLICKHOUSE_PASSWORD', ''),
        'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT', 2),
        'timeout_query'   => env('CLICKHOUSE_TIMEOUT_QUERY', 2),
        'https'           => (bool) env('CLICKHOUSE_HTTPS', false),
        'retries'         => env('CLICKHOUSE_RETRIES', 0),
        'settings'        => [],
        'fix_default_query_builder' => true,
    ],

    // Additional connections — uncomment or add your own.
    //
    // 'clickhouse2' => [
    //     'driver'          => 'clickhouse',
    //     'host'            => env('CLICKHOUSE2_HOST', '127.0.0.1'),
    //     'port'            => env('CLICKHOUSE2_PORT', '8123'),
    //     'database'        => env('CLICKHOUSE2_DATABASE', 'default'),
    //     'username'        => env('CLICKHOUSE2_USERNAME', 'default'),
    //     'password'        => env('CLICKHOUSE2_PASSWORD', ''),
    //     'timeout_connect' => 2,
    //     'timeout_query'   => 2,
    //     'https'           => false,
    //     'retries'         => 0,
    //     'fix_default_query_builder' => true,
    // ],

];
