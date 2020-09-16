<?php


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
        $this->value = $value;
        if ($alias) {
            $this->value .= " AS `$alias`";
        }
    }
}