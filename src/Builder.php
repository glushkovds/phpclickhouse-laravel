<?php


namespace PhpClickHouseLaravel;


use ClickHouseDB\Client;
use ClickHouseDB\Statement;
use Illuminate\Support\Facades\DB;
use Tinderbox\ClickhouseBuilder\Query\BaseBuilder;
use Tinderbox\ClickhouseBuilder\Query\Grammar;

class Builder extends BaseBuilder
{

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
}
