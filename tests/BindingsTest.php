<?php

namespace Tests;

use PhpClickHouseLaravel\Builder;
use Tests\Models\Example;

class BindingsTest extends TestCase
{
    public function testBindings()
    {
        $query = Example::select()
            ->where(function (Builder $q) {
                $q->where('f_int', 1)
                    ->orWhere('f_int', 2)
                    ->orWhere('f_int', 3);
            })
            ->whereIn('f_int2', [10, 11]);
        $this->assertEquals("SELECT * FROM `examples` WHERE (`f_int` = 1 OR `f_int` = 2 OR `f_int` = 3) AND `f_int2` IN (10, 11)", $query->toSql());
    }
}