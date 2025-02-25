<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use ClickHouseDB\Client;
use Closure;
use Illuminate\Database\Connection as BaseConnection;

class Connection extends BaseConnection
{
    public const DEFAULT_NAME = 'clickhouse';

    protected Cluster $cluster;

    public function getCluster(): Cluster
    {
        return $this->cluster;
    }

    public function getClient(): Client
    {
        return $this->cluster->getActiveNode();
    }

    /**
     * @param array $config
     * @return static
     */
    public static function createWithClient(array $config): self
    {
        $conn = new static(null, $config['database'], '', $config);
        $nodeConfigs = [];
        if ($cluster = $config['cluster'] ?? null) {
            foreach ($cluster as $node) {
                $nodeConfigs[] = $node + $config;
            }
        } else {
            $nodeConfigs[] = $config;
        }
        $conn->cluster = new Cluster($nodeConfigs);
        return $conn;
    }

    /** @inheritDoc */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar($this);
    }

    /** @inheritDoc */
    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar();
    }

    /** @inheritDoc */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    /** @inheritDoc */
    public function select($query, $bindings = [], $useReadPdo = true): array
    {
        $query = QueryGrammar::prepareParameters($query);
        return $this->run($query, $bindings, function ($query, $bindings) {
            return $this->cluster->getActiveNode()->select($query, $bindings)->rows();
        });
    }

    /** @inheritDoc */
    public function statement($query, $bindings = []): bool
    {
        $query = QueryGrammar::prepareParameters($query);
        return $this->run($query, $bindings, function ($query, $bindings) {
            return !$this->cluster->getActiveNode()->write($query, $bindings)->isError();
        });
    }

    /** @inheritDoc */
    public function affectingStatement($query, $bindings = []): int
    {
        $query = QueryGrammar::prepareParameters($query);
        return (int)$this->statement($query, $bindings);
    }

    /** @inheritDoc */
    protected function run($query, $bindings, Closure $callback)
    {
        $start = microtime(true);

        $result = $callback($query, $bindings);

        $this->logQuery($query, $bindings, $this->getElapsedTime($start));

        return $result;
    }

    /**
     * Get a new query builder instance.
     *
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    public function query()
    {
        if ($this->config['fix_default_query_builder'] ?? false) {
            return new Builder();
        }
        return parent::query();
    }
}
