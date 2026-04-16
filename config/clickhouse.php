<?php

return [
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
];
