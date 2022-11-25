<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Models\Example;

class BaseTest extends TestCase
{
    public function testMain()
    {
        /** @var \ClickHouseDB\Client $db */
//        $db = DB::connection('clickhouse')->getClient();
        Artisan::call('migrate');
        Example::insertAssoc([['f_int' => 1, 'f_string' => 'zz']]);
        $rows = Example::select()->getRows();
        $this->assertEquals(1, $rows[0]['f_int']);
        $this->assertEquals('zz', $rows[0]['f_string']);
    }
}