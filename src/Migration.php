<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use ClickHouseDB\Client;
use ClickHouseDB\Statement;
use Illuminate\Database\Migrations\Migration as BaseMigration;
use Illuminate\Support\Facades\DB;

class Migration extends BaseMigration
{
    /**
     * @param string $sql
     * @param array $bindings
     * @return Statement
     */
    protected static function write(string $sql, array $bindings = []): Statement
    {
        /** @var Client $client */
        $client = DB::connection('clickhouse')->getClient();

        return $client->write($sql, $bindings);
    }
}
