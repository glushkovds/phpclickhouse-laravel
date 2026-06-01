<?php

namespace Tests;

use ClickHouseDB\Client;
use ClickHouseDB\Query\WhereInFile;
use ClickHouseDB\Query\WriteToFile;
use ClickHouseDB\Statement;
use ClickHouseDB\Transport\CurlerRequest;
use ClickHouseDB\Transport\CurlerResponse;
use PhpClickHouseLaravel\Builder;
use PhpClickHouseLaravel\Connection;
use PhpClickHouseLaravel\LaravelStatement;
use PHPUnit\Framework\TestCase;

class LaravelStatementTest extends TestCase
{
    public function testAllReturnsRowsAsObjects(): void
    {
        $statement = LaravelStatement::fromStatement($this->makeStatement([
            ['id' => 1, 'migration' => '2026_01_01_000000_create_examples', 'batch' => 2],
        ]));

        $this->assertSame([
            ['id' => 1, 'migration' => '2026_01_01_000000_create_examples', 'batch' => 2],
        ], $statement->rows());

        $this->assertEquals([
            (object) ['id' => 1, 'migration' => '2026_01_01_000000_create_examples', 'batch' => 2],
        ], $statement->all());
    }

    public function testBuilderGetReturnsLaravelCompatibleStatement(): void
    {
        $sourceStatement = $this->makeStatement([
            ['id' => 1, 'migration' => '2026_01_01_000000_create_examples', 'batch' => 2],
        ]);

        $client = new class($sourceStatement) extends Client {
            public function __construct(private Statement $statement)
            {
            }

            public function select(
                string $sql,
                array $bindings = [],
                ?WhereInFile $whereInFile = null,
                ?WriteToFile $writeToFile = null,
                array $querySettings = []
            ): Statement {
                return $this->statement;
            }
        };

        $builder = new class($client) extends Builder {
            public function resolveConnection(): Connection
            {
                return new Connection(null, 'default', '', []);
            }
        };

        $result = $builder->select(['*'])->from('migrations')->get();

        $this->assertInstanceOf(Statement::class, $result);
        $this->assertInstanceOf(LaravelStatement::class, $result);
        $this->assertSame('2026_01_01_000000_create_examples', $result->all()[0]->migration);
    }

    private function makeStatement(array $rows): Statement
    {
        $request = new CurlerRequest();
        $request->setRequestExtendedInfo([
            'format' => 'JSON',
            'query' => null,
            'sql' => 'SELECT * FROM migrations',
        ]);

        $response = new CurlerResponse();
        $response->_info = [
            'http_code' => 200,
            'content_type' => 'application/json',
        ];
        $response->_body = json_encode([
            'meta' => [
                ['name' => 'id', 'type' => 'Int32'],
                ['name' => 'migration', 'type' => 'String'],
                ['name' => 'batch', 'type' => 'Int32'],
            ],
            'data' => $rows,
            'rows' => count($rows),
        ]);

        $request->setResponse($response);

        return new Statement($request);
    }
}
