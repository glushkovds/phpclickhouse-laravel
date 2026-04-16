<?php

declare(strict_types=1);

namespace Tests\Unit;

use ClickHouseDB\Client;
use PhpClickHouseLaravel\Builder;
use PHPUnit\Framework\TestCase;

class BuilderSqlTest extends TestCase
{
    private Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(Client::class);
    }

    public function test_where_in_generates_in_clause(): void
    {
        $sql = (new Builder($this->mockClient))
            ->from('examples')
            ->whereIn('f_string', ['zz'])
            ->toSql();

        $this->assertEquals(
            "SELECT * FROM `examples` WHERE `f_string` IN ('zz')",
            $sql
        );
    }

    public function test_where_between_generates_between_clause(): void
    {
        $sql = (new Builder($this->mockClient))
            ->from('examples')
            ->whereBetween('f_int', [1, 2])
            ->toSql();

        $this->assertEquals(
            'SELECT * FROM `examples` WHERE `f_int` BETWEEN 1 AND 2',
            $sql
        );
    }

    public function test_where_in_and_between_combine_with_and(): void
    {
        $sql = (new Builder($this->mockClient))
            ->from('examples')
            ->whereIn('f_string', ['zz'])
            ->whereBetween('f_int', [1, 2])
            ->toSql();

        $this->assertEquals(
            "SELECT * FROM `examples` WHERE `f_string` IN ('zz') AND `f_int` BETWEEN 1 AND 2",
            $sql
        );
    }

    public function test_or_where_groups_with_parentheses(): void
    {
        $sql = (new Builder($this->mockClient))
            ->from('examples')
            ->where(function (Builder $q) {
                $q->where('f_int', 1)->orWhere('f_int', 2);
            })
            ->toSql();

        $this->assertEquals(
            'SELECT * FROM `examples` WHERE (`f_int` = 1 OR `f_int` = 2)',
            $sql
        );
    }

    public function test_simple_where_equality(): void
    {
        $sql = (new Builder($this->mockClient))
            ->from('examples')
            ->where('f_int', 1)
            ->toSql();

        $this->assertEquals(
            'SELECT * FROM `examples` WHERE `f_int` = 1',
            $sql
        );
    }
}
