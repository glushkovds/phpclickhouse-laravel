<?php


namespace PhpClickHouseLaravel;


use Illuminate\Support\ServiceProvider;

/**
 * Service provider to connect Clickhouse driver in Laravel.
 */
class ClickhouseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $db = $this->app->make('db');

        $db->extend('clickhouse', function ($config, $name) {
            $config['name'] = $name;
            $conn = Connection::createWithClient($config);
            return $conn;
        });
    }
}
