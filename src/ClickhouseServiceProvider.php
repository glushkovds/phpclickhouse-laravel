<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider to connect Clickhouse driver in Laravel.
 */
class ClickhouseServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $db = $this->app->make('db');

        $db->extend('clickhouse', function ($config, $name) {
            $config['name'] = $name;

            return Connection::createWithClient($config);
        });

        BaseModel::setEventDispatcher($this->app['events']);
    }
}
