<?php


namespace PhpClickHouseLaravel;


use ClickHouseDB\Client;
use Illuminate\Support\Facades\DB;

class BaseModel
{

    /**
     * @var string table name
     */
    protected $table;

    /**
     * @return Client
     */
    public static function getClient()
    {
        return DB::connection('clickhouse')->getClient();
    }

    /**
     * Bulk insert into Clickhouse database
     * @param array[] $rows
     * @return \ClickHouseDB\Statement
     */
    public static function insert($rows)
    {
        return static::getClient()->insert((new static)->table, $rows);
    }

    /**
     * Prepare each row by calling static::prepareFromRequest to bulk insert into database
     * @param array[] $rows
     * @return \ClickHouseDB\Statement
     */
    public static function prepareAndInsert($rows)
    {
        $rows = array_map('static::prepareFromRequest', $rows);
        return static::getClient()->insert((new static)->table, $rows);
    }

    /**
     * Need to overwrite in nested models
     * @param array $row
     * @return array
     */
    public static function prepareFromRequest($row)
    {
        return $row;
    }
}
