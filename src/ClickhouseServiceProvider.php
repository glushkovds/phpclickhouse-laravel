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
    public function register(): void
    {
        $this->mergeConfigFrom($this->packageConfigPath(), 'clickhouse');
        $this->registerDefaultConnectionConfig();
    }

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

    protected function registerDefaultConnectionConfig(): void
    {
        $configuredConnection = $this->app['config']->get('database.connections.'.Connection::DEFAULT_NAME);

        $this->app['config']->set(
            'database.connections.'.Connection::DEFAULT_NAME,
            array_replace_recursive($this->defaultConnectionConfig(), (array)$configuredConnection)
        );
    }

    protected function defaultConnectionConfig(): array
    {
        return (array)$this->app['config']->get('clickhouse.connection', []);
    }

    protected function packageConfigPath(): string
    {
        return __DIR__.'/../config/clickhouse.php';
    }
}
