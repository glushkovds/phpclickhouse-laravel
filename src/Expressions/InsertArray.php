<?php


namespace PhpClickHouseLaravel\Expressions;


use ClickHouseDB\Query\Expression\Expression;

/**
 * Class InsertArray
 * @package PhpClickHouseLaravel
 *
 * Used to insert Array datatype
 * @link https://clickhouse.tech/docs/ru/sql-reference/data-types/array/
 * @example Model::insertAssoc([[1,'str',new InsertArray(['a','b'])]]);
 */
class InsertArray implements Expression
{
    const TYPE_STRING = 'string';
    const TYPE_STRING_ESCAPE = 'string_e';
    // Can also be used for float types
    const TYPE_DECIMAL = 'decimal';
    const TYPE_INT = 'int';

    private $expression;

    public function __construct(array $items, $type = self::TYPE_STRING)
    {
        if (self::TYPE_INT == $type) {
            $this->expression = "[" . implode(",", array_map('intval', $items)) . "]";
        } elseif (self::TYPE_DECIMAL == $type) {
            $this->expression = "[" . implode(",", array_map('floatval', $items)) . "]";
        } elseif (self::TYPE_STRING_ESCAPE == $type) {
            $this->expression = "['" . implode("','", array_map(function ($item) {
                    return str_replace("'", "\'", $item);
                }, $items)) . "']";
        } else {
            $this->expression = "['" . implode("','", $items) . "']";
        }
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
