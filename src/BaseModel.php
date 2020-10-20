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
     * Bulk insert rows as associative array into Clickhouse database
     * @param array[] $rows
     * @return \ClickHouseDB\Statement
     */
    public static function insertAssoc($rows)
    {
        return static::getClient()->insertAssocBulk((new static)->table, $rows);
    }

    /**
     * Prepare each row by calling static::prepareAssocFromRequest to bulk insert into database
     * @param array[] $rows
     * @return \ClickHouseDB\Statement
     */
    public static function prepareAndInsertAssoc($rows)
    {
        $rows = array_map('static::prepareAssocFromRequest', $rows);
        return static::getClient()->insertAssocBulk((new static)->table, $rows);
    }

    /**
     * Prepare row to insert into DB, non associative array
     * Need to overwrite in nested models
     * @param array $row
     * @return array
     */
    public static function prepareFromRequest($row)
    {
        return $row;
    }

    /**
     * Prepare row to insert into DB, associative array
     * Need to overwrite in nested models
     * @param array $row
     * @return array
     */
    public static function prepareAssocFromRequest($row)
    {
        return $row;
    }

    /**
     * @param array $select optional = ['*']
     * @return Builder
     */
    public static function select($select = ['*'])
    {
        return (new Builder())->select($select)->from((new static)->table);
    }

}
