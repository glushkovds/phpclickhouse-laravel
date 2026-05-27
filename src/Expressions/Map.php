<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel\Expressions;

use ClickHouseDB\Query\Expression\Expression;

/**
 * Used to insert Array datatype
 * @link https://clickhouse.com/docs/sql-reference/data-types/map/
 * @example Model::insertAssoc([[1,'str',new Map(['a'=>'b','c'=>'d'])]]);
 */
class Map implements Expression
{
    private string $expression;

    public function __construct(array $value)
    {
        $value = json_encode($value, JSON_FORCE_OBJECT);
        // Convert to ClickHouse format because “ will be used for columns.
        // example: {"key": 1} -> {'key': 1}
        $value = str_replace('"', "'", $value);
        $this->expression = $value;
    }

    public function needsEncoding(): bool
    {
        return false;
    }

    public function getValue(): string
    {
        return $this->expression;
    }
}
