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
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
        $isPretend = $trace['5']['function'] == 'pretend';

        if ($isPretend) {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $name = static::class;
            $output->writeln("<comment>Clickhouse</comment> <info>{$name}:</info> $sql");
            return new Statement(new \ClickHouseDB\Transport\CurlerRequest());
        }

        /** @var Client $client */
        $client = DB::connection('clickhouse')->getClient();

        return $client->write($sql, $bindings);
    }
}
