<?php


namespace PhpClickHouseLaravel;


use ClickHouseDB\Client;
use ClickHouseDB\Exception\QueryException;
use ClickHouseDB\Statement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        try {
            /** @var Client $db */
            $db = DB::connection('clickhouse')->getClient();
            return $db->select($this->toSql());
        } catch (\Throwable $e) {
            Log::warning('Reached error', ['error' => $e->getCode(), 'message' => $e->getMessage()]);
            if ($e->getCode() == 28) {
                Log::warning('Reached resolving timeout');
            }
            /** @var Client $db */
            $db = DB::connection('clickhouse')->getClient();
            return $db->select($this->toSql());
        }
    }

    /**
     * @return array
     */
    public function getRows()
    {
        return $this->get()->rows();
    }
}
