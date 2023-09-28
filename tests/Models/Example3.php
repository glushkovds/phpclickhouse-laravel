<?php

namespace Tests\Models;

use PhpClickHouseLaravel\BaseModel;

/**
 * @property bool $f_bool
 * @property int $f_int
 */
class Example3 extends BaseModel
{
    protected $table = 'examples3';
    protected $casts = ['f_bool' => 'boolean'];
}