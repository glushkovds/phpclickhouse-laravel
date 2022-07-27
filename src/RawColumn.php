<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use Tinderbox\ClickhouseBuilder\Query\Expression;

class RawColumn extends Expression
{
    /**
     * Create a new raw query expression.
     *
     * @param mixed $value
     * @param null $alias
     */
    public function __construct($value, $alias = null)
    {
        if ($alias) {
            $value .= " AS `$alias`";
        }
        parent::__construct($value);
    }
}
