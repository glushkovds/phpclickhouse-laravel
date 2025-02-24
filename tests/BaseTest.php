<?php

namespace Tests;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PhpClickHouseLaravel\Builder;
use Tests\Models\Example;

class BaseTest extends TestCase
{
    public function testWorkWithClient()
    {
        /** @var \ClickHouseDB\Client $db */
        $db = DB::connection('clickhouse')->getClient();
        $db->write("TRUNCATE TABLE examples");
        $db->insert('examples', [[100, 'string']], ['f_int', 'f_string']);
        $db->write("ALTER TABLE examples UPDATE f_string='updated string' WHERE f_int=100");
        usleep(1e4);
        $rows = $db->select("SELECT * FROM examples LIMIT 1")->rows();
        $this->assertEquals(100, $rows[0]['f_int']);
        $this->assertEquals('updated string', $rows[0]['f_string']);
    }

    public function testSimpleModelInsertAndSelect()
    {
        Example::truncate();
        Example::insertAssoc([['f_int' => 1, 'f_string' => 'zz']]);
        $rows = Example::select()->getRows();
        $this->assertEquals(1, $rows[0]['f_int']);
        $this->assertEquals('zz', $rows[0]['f_string']);
    }

    public function testSimpleModelInsertAndPaginate()
    {
        Example::truncate();
        Example::insertAssoc([
            ['f_int' => 1, 'f_string' => 'zz'],
            ['f_int' => 2, 'f_string' => 'aa'],
            ['f_int' => 3, 'f_string' => 'bb'],
        ]);
        $result = Example::select()->paginate(2);
        $this->assertTrue($result instanceof LengthAwarePaginator);
        $this->assertEquals(3, $result->total());
        $this->assertEquals(2, $result->items()->count());
        $this->assertEquals(1, $result->items()->first()['f_int']);
        $this->assertEquals('zz', $result->items()->first()['f_string']);
    }

    public function testSimpleModelInsertAndSimplePaginate()
    {
        Example::truncate();
        Example::insertAssoc([
            ['f_int' => 1, 'f_string' => 'zz'],
            ['f_int' => 2, 'f_string' => 'aa'],
            ['f_int' => 3, 'f_string' => 'bb'],
        ]);
        $result = Example::select()->paginate(2);
        $this->assertTrue($result instanceof Paginator);
        $this->assertEquals(2, $result->items()->count());
        $this->assertEquals(2, $result->perPage());
        $this->assertEquals(1, $result->items()->first()['f_int']);
        $this->assertEquals('zz', $result->items()->first()['f_string']);
    }

    public function testMultipleWheres()
    {
        Example::truncate();
        Example::insertAssoc([['f_int' => 1, 'f_string' => 'zz']]);
        $query = Example::select()
            ->whereIn('f_string', ['zz'])
            ->whereBetween('f_int', [1, 2]);
        $this->assertEquals("SELECT * FROM `examples` WHERE `f_string` IN ('zz') AND `f_int` BETWEEN 1 AND 2", $query->toSql());
        $rows = $query->getRows();
        $this->assertNotEmpty($rows);
    }

    public function testPretendMigration()
    {
        /** @var \ClickHouseDB\Client $db */
        $db = DB::connection('clickhouse')->getClient();
        $migration = file_get_contents(base_path('database/migrations/2022_01_01_000000_example.php'));

        // Default mode
        $tableName = 'examples_' . rand(0, 9999999999999999);
        $migrationTmp = str_replace('examples', $tableName, $migration);
        file_put_contents(base_path('database/migrations/example_' . rand(0, 9999999999999999) . '.php'), $migrationTmp);
        Artisan::call('migrate');
        $exist = $db->select("EXISTS $tableName")->fetchOne('result');
        $this->assertEquals(1, $exist);

        // Pretend mode
        $tableName = 'examples_' . rand(0, 9999999999999999);
        $migrationTmp = str_replace('examples', $tableName, $migration);
        file_put_contents(base_path('database/migrations/example_' . rand(0, 9999999999999999) . '.php'), $migrationTmp);
        Artisan::call('migrate', ['--pretend' => true]);
        $exist = $db->select("EXISTS $tableName")->fetchOne('result');
        $this->assertEquals(0, $exist);
    }

    public function testOrWhere()
    {
        $query = Example::select()->where(function (Builder $q) {
            $q->where('f_int', 1)->orWhere('f_int', 2);
        });
        $this->assertEquals("SELECT * FROM `examples` WHERE (`f_int` = 1 OR `f_int` = 2)", $query->toSql());
    }
}