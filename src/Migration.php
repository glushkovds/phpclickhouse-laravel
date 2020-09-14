<?php


namespace PhpClickHouseLaravel;


use Illuminate\Support\Facades\DB;

class Migration extends \Illuminate\Database\Migrations\Migration
{
    /**
     *
     * @param string $sql
     * @return \ClickHouseDB\Statement
     */
    protected static function write(string $sql)
    {
        /** @var \ClickHouseDB\Client $client */
        $client = DB::connection('clickhouse')->getClient();
        return $client->write($sql);
    }
}
