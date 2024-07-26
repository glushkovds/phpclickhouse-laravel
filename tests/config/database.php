<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => 'clickhouse',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'clickhouse' => [
            'driver' => 'clickhouse',
            'host' => env('CLICKHOUSE_HOST'),
            'port' => env('CLICKHOUSE_PORT', '8123'),
            'database' => env('CLICKHOUSE_DATABASE', 'default'),
            'username' => env('CLICKHOUSE_USERNAME', 'default'),
            'password' => env('CLICKHOUSE_PASSWORD', ''),
            'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT', 2),
            'timeout_query' => env('CLICKHOUSE_TIMEOUT_QUERY', 2),
            'https' => (bool)env('CLICKHOUSE_HTTPS', null),
            'retries' => env('CLICKHOUSE_RETRIES', 0),
            'settings' => [
                'max_partitions_per_insert_block' => 300,
            ],
        ],

        'clickhouse2' => [
            'driver' => 'clickhouse',
            'host' => 'clickhouse02',
            'port' => '8123',
            'database' => 'default',
            'username' => 'default',
            'password' => '',
            'timeout_connect' => 2,
            'timeout_query' => 2,
            'https' => false,
            'retries' => 0,
        ],

        'clickhouse-cluster' => [
            'driver' => 'clickhouse',
            'cluster' => [
                [
                    'host' => 'clickhouse01',
                    'port' => '8123',
                ],
                [
                    'host' => 'clickhouse02',
                    'port' => '8123',
                ],
            ],
            'database' => 'default',
            'username' => 'default',
            'password' => '',
            'timeout_connect' => 2,
            'timeout_query' => 2,
            'https' => false,
            'retries' => 0,
        ],

        'problem-clickhouse-cluster' => [
            'driver' => 'clickhouse',
            'cluster' => [
                [
                    'host' => 'clickhouse03', // non-existent node
                    'port' => '8123',
                ],
                [
                    'host' => 'clickhouse02',
                    'port' => '8123',
                ],
            ],
            'database' => 'default',
            'username' => 'default',
            'password' => '',
            'timeout_connect' => 2,
            'timeout_query' => 2,
            'https' => false,
            'retries' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

];
