<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RollbackTest extends TestCase
{
    public function testMigrationRollbackWorksWithClickhouseMigrationRepository(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $tableName = 'rollback_test_' . $suffix;
        $migrationName = '2099_01_01_000000_create_' . $tableName;
        $migrationPath = database_path('migrations/' . $migrationName . '.php');

        $client = DB::connection('clickhouse')->getClient();
        $client->write('DROP TABLE IF EXISTS ' . $tableName);

        $this->assertNotFalse(file_put_contents($migrationPath, $this->migrationStub($tableName)));

        try {
            $this->assertSame(0, Artisan::call('migrate', [
                '--path' => [$migrationPath],
                '--realpath' => true,
            ]));

            $this->assertSame(1, $this->tableExists($tableName));
            $this->assertTrue($this->migrationIsLogged($migrationName));

            $this->assertSame(0, Artisan::call('migrate:rollback', [
                '--path' => [$migrationPath],
                '--realpath' => true,
                '--step' => 1,
            ]));

            $this->assertSame(0, $this->tableExists($tableName));
            $this->assertTrue($this->waitForMigrationLogDeletion($migrationName));
        } finally {
            if (file_exists($migrationPath)) {
                unlink($migrationPath);
            }

            $client->write('DROP TABLE IF EXISTS ' . $tableName);

            if ($this->migrationIsLogged($migrationName)) {
                DB::table('migrations')->where('migration', $migrationName)->delete();
            }
        }
    }

    private function tableExists(string $tableName): int
    {
        return (int) DB::connection('clickhouse')
            ->getClient()
            ->select('EXISTS ' . $tableName)
            ->fetchOne('result');
    }

    private function migrationIsLogged(string $migrationName): bool
    {
        return count(DB::table('migrations')
                ->where('migration', $migrationName)
                ->get()
                ->all()) > 0;
    }

    private function waitForMigrationLogDeletion(string $migrationName): bool
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            if (!$this->migrationIsLogged($migrationName)) {
                return true;
            }

            usleep(100000);
        }

        return false;
    }

    private function migrationStub(string $tableName): string
    {
        return <<<PHP
<?php

return new class extends \PhpClickHouseLaravel\Migration {
    public function up()
    {
        static::write("
            CREATE TABLE IF NOT EXISTS {$tableName} (
                id Int64,
                value String
            )
            ENGINE = MergeTree()
            ORDER BY (id)
        ");
    }

    public function down()
    {
        static::write('DROP TABLE IF EXISTS {$tableName}');
    }
};
PHP;
    }
}
