<?php

namespace PhpClickHouseLaravel;

use ClickHouseDB\Client;
use ClickHouseDB\Exception\TransportException;
use ClickHouseDB\Statement;

class Cluster
{
    /**
     * @var Client[]
     */
    protected array $nodes;
    protected int $activeNodeIndex;

    public function __construct(
        protected array $nodeConfigs
    ) {
        foreach ($this->nodeConfigs as $index => $nodeConfig) {
            try {
                $this->nodes[$index] = static::createClient($nodeConfig);
                $this->nodes[$index]->ping(true);
                $this->activeNodeIndex = $index;
                break;
            } catch (TransportException $e) {
            }
        }
        if (!isset($this->activeNodeIndex)) {
            throw $e ?? new TransportException('No nodes are available');
        }
    }

    public function write(string $sql, array $bindings = [], bool $exception = true): ?Statement
    {
        foreach ($this->nodeConfigs as $index => $config) {
            if (empty($this->nodes[$index])) {
                $this->nodes[$index] = static::createClient($config);
            }
            $statement = $this->nodes[$index]->write($sql, $bindings, $exception);
        }
        return $statement ?? null;
    }

    public function getActiveNode(): Client
    {
        return $this->nodes[$this->activeNodeIndex];
    }

    /**
     * Switch active node to the next available node
     * @return void
     */
    public function slideNode(): void
    {
        $configCount = count($this->nodeConfigs);
        if ($configCount < 2) {
            return;
        }
        for ($i = 0; $i < $configCount; $i++) {
            $nextIndex = $this->activeNodeIndex + 1;
            if ($configCount == $nextIndex) {
                $nextIndex = 0;
            }
            try {
                $this->nodes[$nextIndex] ??= static::createClient($this->nodeConfigs[$nextIndex]);
                $this->nodes[$nextIndex]->ping(true);
                $this->activeNodeIndex = $nextIndex;
                break;
            } catch (TransportException) {
            }
        }
    }

    protected static function createClient(array $config): Client
    {
        $client = new Client($config);
        $client->database($config['database']);
        $client->setTimeout((int)$config['timeout_query']);
        $client->setConnectTimeOut((int)$config['timeout_connect']);
        if ($configSettings =& $config['settings']) {
            $settings = $client->settings();
            foreach ($configSettings as $sName => $sValue) {
                $settings->set($sName, $sValue);
            }
        }
        if ($retries = (int)($config['retries'] ?? null)) {
            $curler = new CurlerRollingWithRetries();
            $curler->setRetries($retries);
            $client->transport()->setDirtyCurler($curler);
        }
        return $client;
    }
}
