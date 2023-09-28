<?php

namespace Tests;

use Tests\Models\Example3;

class CastsTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Example3::truncate();
    }

    public function testCasts()
    {
        Example3::insertAssoc([['f_int' => 1, 'f_bool' => false]]);
        $rows = Example3::where('f_int', 1)->getRows();
        $this->assertEquals(false, $rows[0]['f_bool']);

        Example3::truncate();
        Example3::insertBulk([[2, false]], ['f_int', 'f_bool']);
        $rows = Example3::where('f_int', 2)->getRows();
        $this->assertEquals(false, $rows[0]['f_bool']);

        Example3::truncate();
        $one = new Example3();
        $one->f_int = 3;
        $one->f_bool = false;
        $one->save();
        $rows = Example3::where('f_int', 3)->getRows();
        $this->assertEquals(false, $rows[0]['f_bool']);
    }
}