<?php

return [
    'connection' => [
        'driver' => 'clickhouse',
        'host' => env('CLICKHOUSE_HOST', 'localhost'),
        'port' => (int) env('CLICKHOUSE_PORT', 8123),
        'database' => env('CLICKHOUSE_DATABASE', 'default'),
        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', ''),
        'timeout_connect' => (int)env('CLICKHOUSE_TIMEOUT_CONNECT', 2),
        'timeout_query' => (int)env('CLICKHOUSE_TIMEOUT_QUERY', 2),
        'https' => (bool)env('CLICKHOUSE_HTTPS', false),
        'retries' => (int)env('CLICKHOUSE_RETRIES', 0),
        'settings' => [
            'max_partitions_per_insert_block' => (int)env('CLICKHOUSE_MAX_PARTITIONS_PER_INSERT_BLOCK', 300),
        ],
        'fix_default_query_builder' => (bool)env('CLICKHOUSE_FIX_DEFAULT_QUERY_BUILDER', true),
    ],
];
