<?php


namespace PhpClickHouseLaravel;


use ClickHouseDB\Client;
use ClickHouseDB\Statement;
use Illuminate\Support\Facades\DB;
use Tinderbox\ClickhouseBuilder\Query\BaseBuilder;
use Tinderbox\ClickhouseBuilder\Query\Grammar;

class Builder extends BaseBuilder
{

    /** @var string */
    protected $tableSources;

    public function __construct()
    {
        $this->grammar = new Grammar();
    }

    /**
     * @return Statement
     */
    public function get()
    {
        /** @var Client $db */
        $db = DB::connection('clickhouse')->getClient();
        return $db->select($this->toSql());
    }

    /**
     * @return array
     */
    public function getRows()
    {
        return $this->get()->rows();
    }

    /**
     * Chunk the results of the query.
     *
     * @param int $count
     * @param callable $callback
     */
    public function chunk(int $count, callable $callback)
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
    public function setSourcesTable(string $table)
    {
        $this->tableSources = $table;
        return $this;
    }

    /**
     * Note! This is a heavy operation not designed for frequent use.
     * @return Statement
     */
    public function delete()
    {
        $table = $this->tableSources ?? $this->getFrom()->getTable();
        $sql = "ALTER TABLE $table DELETE " . $this->grammar->compileWheresComponent($this, $this->getWheres());
        /** @var Client $db */
        $db = DB::connection('clickhouse')->getClient();
        return $db->write($sql);
    }

}
