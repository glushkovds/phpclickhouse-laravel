<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use PhpClickHouseLaravel\Builder;
use Tests\Models\Example;

class BindingsTest extends TestCase
{
    public function testBindingsByModel()
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

    public function testBindingsByTableMethod()
    {
        $query = DB::table('examples')
            ->where(function (Builder $q) {
                $q->where('f_int', 1)
                    ->orWhere('f_int', 2)
                    ->orWhere('f_int', 3);
            })
            ->whereIn('f_int2', [10, 11]);
        $this->assertEquals("SELECT * FROM `examples` WHERE (`f_int` = 1 OR `f_int` = 2 OR `f_int` = 3) AND `f_int2` IN (10, 11)", $query->toSql());
    }
}