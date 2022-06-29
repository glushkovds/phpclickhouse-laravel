<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use ClickHouseDB\Client;
use Illuminate\Database\Connection as BaseConnection;

class Connection extends BaseConnection
{
    /** @var Client */
    protected $client;

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param array $config
     * @return static
     */
    public static function createWithClient(array $config)
    {
        $conn = new static(null, $config['database'], '', $config);
        $conn->client = new Client($config);
        $conn->client->database($config['database']);
        $conn->client->setTimeout($config['timeout_query']);
        $conn->client->setConnectTimeOut($config['timeout_connect']);
        if ($configSettings =& $config['settings']) {
            $settings = $conn->getClient()->settings();
            foreach ($configSettings as $sName => $sValue) {
                $settings->set($sName, $sValue);
            }
        }
        if ($retries = (int)($config['retries'] ?? null)) {
            $curler = new CurlerRollingWithRetries();
            $curler->setRetries($retries);
            $conn->client->transport()->setDirtyCurler($curler);
        }

        return $conn;
    }

    /** @inheritDoc */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar();
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

        return $this->client->select($query, $bindings)->rows();
    }

    /** @inheritDoc */
    public function statement($query, $bindings = []): bool
    {
        return !$this->client->write($query, $bindings)->isError();
    }

    /** @inheritDoc */
    public function affectingStatement($query, $bindings = []): int
    {
        return (int)$this->statement($query, $bindings);
    }
}
