<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use PhpClickHouseLaravel\QueryGrammar;
use PHPUnit\Framework\TestCase;

class QueryGrammarTest extends TestCase
{
    public function test_prepare_parameters_leaves_sql_unchanged_when_no_marker(): void
    {
        $result = QueryGrammar::prepareParameters('SELECT 1');

        $this->assertSame('SELECT 1', $result);
    }

    public function test_prepare_parameters_replaces_single_marker(): void
    {
        $sql = 'SELECT * FROM t WHERE id = ' . QueryGrammar::PARAMETER_SIGN;

        $result = QueryGrammar::prepareParameters($sql);

        $this->assertSame('SELECT * FROM t WHERE id = :0', $result);
    }

    public function test_prepare_parameters_replaces_multiple_markers_sequentially(): void
    {
        $sign = QueryGrammar::PARAMETER_SIGN;
        $sql = "SELECT * FROM t WHERE a = $sign AND b = $sign AND c = $sign";

        $result = QueryGrammar::prepareParameters($sql);

        $this->assertSame('SELECT * FROM t WHERE a = :0 AND b = :1 AND c = :2', $result);
    }

    public function test_parameter_returns_parameter_sign_for_plain_value(): void
    {
        $connection = $this->createMock(Connection::class);
        $grammar = new QueryGrammar($connection);

        $result = $grammar->parameter('foo');

        $this->assertSame(QueryGrammar::PARAMETER_SIGN, $result);
    }

    public function test_parameter_returns_raw_value_for_expression(): void
    {
        $connection = $this->createMock(Connection::class);
        $grammar = new QueryGrammar($connection);
        $expression = new Expression('RAW_SQL');

        $result = $grammar->parameter($expression);

        $this->assertSame('RAW_SQL', $result);
    }
}
