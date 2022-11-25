<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use ClickHouseDB\Client;
use ClickHouseDB\Statement;
use ClickHouseDB\Transport\CurlerRequest;
use Illuminate\Database\Migrations\Migration as BaseMigration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

class Migration extends BaseMigration
{
    /**
     * @param string $sql
     * @param array $bindings
     * @return Statement
     */
    protected static function write(string $sql, array $bindings = []): Statement
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $trace) {
            if ($trace['function'] == 'pretend') {
                $name = static::class;
                (new ConsoleOutput())->writeln("<comment>Clickhouse</comment> <info>$name:</info> $sql");
                return new Statement(new CurlerRequest());
            }
        }

        /** @var Client $client */
        $client = DB::connection('clickhouse')->getClient();
        return $client->write($sql, $bindings);
    }
}
