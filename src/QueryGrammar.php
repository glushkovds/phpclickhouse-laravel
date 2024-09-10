<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class QueryGrammar extends Grammar
{
    const PARAMETER_SIGN = '#@?';

    /** @inheritDoc */
    public function parameter($value)
    {
        return $this->isExpression($value) ? $this->getValue($value) : self::PARAMETER_SIGN;
    }

    /**
     * Second part of trick to change signs "?" to ":0", ":1" and so on
     * @param string $sql
     * @return string
     */
    public static function prepareParameters(string $sql): string
    {
        $parameterNum = 0;
        while (($pos = strpos($sql, QueryGrammar::PARAMETER_SIGN)) !== false) {
            $sql = substr_replace($sql, ":$parameterNum", $pos, strlen(QueryGrammar::PARAMETER_SIGN));
            $parameterNum++;
        }

        return $sql;
    }

    /** @inheritDoc */
    protected function compileDeleteWithoutJoins(Builder $query, $table, $where): string
    {
        return "alter table {$table} delete {$where}";
    }
}
