<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Connection;
use Illuminate\Support\Fluent;
use PhpClickHouseLaravel\SchemaGrammar;
use PHPUnit\Framework\TestCase;

class SchemaGrammarTest extends TestCase
{
    public function test_compile_table_exists_queries_system_tables(): void
    {
        $connection = $this->createMock(Connection::class);
        $grammar = new SchemaGrammar($connection);

        $result = $grammar->compileTableExists('mydb', 'users');

        $this->assertSame(
            "select * from system.tables where database = 'mydb' and name = 'users'",
            $result
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('typeMappings')]
    public function test_column_type_mappings(string $method, string $expected): void
    {
        $connection = $this->createMock(Connection::class);
        $grammar = new SchemaGrammar($connection);
        $column = new Fluent(['name' => 'f']);

        $ref = new \ReflectionMethod($grammar, $method);
        $result = $ref->invoke($grammar, $column);

        $this->assertSame($expected, $result);
    }

    public static function typeMappings(): array
    {
        return [
            ['typeTinyInteger',  'Int16'],
            ['typeInteger',      'Int32'],
            ['typeBigInteger',   'Int64'],
            ['typeString',       'String'],
            ['typeText',         'String'],
            ['typeMediumText',   'String'],
            ['typeLongText',     'String'],
            ['typeTimestamp',    'DateTime'],
        ];
    }
}
