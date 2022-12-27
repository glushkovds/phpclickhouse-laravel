<?php

namespace Tests;

use PhpClickHouseLaravel\RawColumn;
use Tests\Models\Example;

class UpdateTest extends TestCase
{
    public function testUpdate()
    {
        Example::truncate();
        Example::insertAssoc([['f_int' => 1, 'f_int2' => 2, 'f_string' => 'a']]);
        Example::where('f_int', 1)->update([
            'f_int2' => 3,
            'f_string' => 'b',
            'created_at' => new RawColumn('created_at + INTERVAL 1 YEAR')
        ]);
        usleep(3e4); // some lag in clickhouse server
        $rows = Example::where('f_int', 1)->getRows();
        $this->assertCount(1, $rows);
        $this->assertEquals(3, $rows[0]['f_int2']);
        $this->assertEquals('b', $rows[0]['f_string']);
        $this->assertEquals(date('Y') + 1, substr($rows[0]['created_at'], 0, 4));
    }
}