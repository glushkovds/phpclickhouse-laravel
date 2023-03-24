<?php

namespace Tests\Models;

use PhpClickHouseLaravel\BaseModel;

class Example2 extends BaseModel
{
    protected $connection = 'clickhouse2';
    protected $table = 'examples2';
}