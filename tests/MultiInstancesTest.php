<?php

use Illuminate\Support\Facades\DB;
use Tests\Models\Example2;
use Tests\TestCase;

class MultiInstancesTest extends TestCase
{
    public function testSecondConnection()
    {
        Example2::truncate();
        Example2::insertAssoc([['f_int' => 1, 'f_int2' => 2, 'f_string' => 'a']]);
        usleep(3e4); // some lag in clickhouse server
        $rows = Example2::where('f_int', 1)->getRows();
        $this->assertCount(1, $rows);
        $this->assertEquals(2, $rows[0]['f_int2']);
        /** @var \ClickHouseDB\Client $db */
        $db = DB::connection('clickhouse2')->getClient();
        $rows = $db->select("SELECT * FROM examples2 LIMIT 1")->rows();
        $this->assertEquals(1, $rows[0]['f_int']);
    }
}