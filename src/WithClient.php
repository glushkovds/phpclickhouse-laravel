<?php

namespace PhpClickHouseLaravel;

use ClickHouseDB\Client;
use Illuminate\Support\Facades\DB;

trait WithClient
{
    public function getClient(): Client
    {
        return DB::connection($this->connection)->getClient();
    }
}