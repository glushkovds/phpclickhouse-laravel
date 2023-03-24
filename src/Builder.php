<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use ClickHouseDB\Client;
use ClickHouseDB\Statement;
use Illuminate\Support\Facades\DB;
use PhpClickHouseLaravel\Exceptions\QueryException;
use Tinderbox\ClickhouseBuilder\Query\BaseBuilder;
use Tinderbox\ClickhouseBuilder\Query\Grammar;

class Builder extends BaseBuilder
{

    use WithClient;

    /** @var string */
    protected $tableSources;
    /** @var Client */
    protected $client;

    /**
     * The name of the database connection to use.
     *
     * @var string|null
     */
    protected $connection = Connection::DEFAULT_NAME;

    public function __construct(Client $client = null)
    {
        $this->grammar = new Grammar();
        $this->client = $client ?? $this->getThisClient();
    }

    /**
     * @return Statement
     */
    public function get(): Statement
    {
        return $this->client->select($this->toSql());
    }

    /**
     * @return array
     */
    public function getRows(): array
    {
        return $this->get()->rows();
    }

    /**
     * Chunk the results of the query.
     *
     * @param int $count
     * @param callable $callback
     */
    public function chunk(int $count, callable $callback): void
    {
        $offset = 0;
        do {
            $rows = $this->limit($count, $offset)->getRows();
            $callback($rows);
            $offset += $count;
        } while ($rows);
    }

    /**
     * For delete query
     * @param string $table
     * @return $this
     */
    public function setSourcesTable(string $table): self
    {
        $this->tableSources = $table;

        return $this;
    }

    /**
     * Note! This is a heavy operation not designed for frequent use.
     * @return Statement
     */
    public function delete(): Statement
    {
        $table = $this->tableSources ?? $this->getFrom()->getTable();
        $sql = "ALTER TABLE $table DELETE " . $this->grammar->compileWheresComponent($this, $this->getWheres());
        return $this->client->write($sql);
    }

    /**
     * Note! This is a heavy operation not designed for frequent use.
     * @return Statement
     */
    public function update(array $values): Statement
    {
        if (empty($values)) {
            throw QueryException::cannotUpdateEmptyValues();
        }
        $table = $this->tableSources ?? $this->getFrom()->getTable();
        $set = [];
        foreach ($values as $key => $value) {
            $set[] = "`$key` = " . $this->grammar->wrap($value);
        }
        $sql = "ALTER TABLE $table UPDATE " . implode(', ', $set) . ' '
            . $this->grammar->compileWheresComponent($this, $this->getWheres());
        return $this->client->write($sql);
    }

}
