<?php

namespace Tests\Models;

use PhpClickHouseLaravel\BaseModel;

/**
 * Points at a table that does not exist so insert calls fail at the HTTP
 * level. Used by buffered-insert tests to verify failure-path behavior.
 */
class ExampleNonExistent extends BaseModel
{
    protected $table = 'examples_nonexistent_for_buffer_tests';
}
