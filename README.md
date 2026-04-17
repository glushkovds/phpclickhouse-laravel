![Tests](https://github.com/oralunal/phpclickhouse-laravel/actions/workflows/tests.yml/badge.svg)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/oralunal/phpclickhouse-laravel.svg?style=flat-square)](https://packagist.org/packages/oralunal/phpclickhouse-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/oralunal/phpclickhouse-laravel.svg?style=flat-square)](https://packagist.org/packages/oralunal/phpclickhouse-laravel)

# phpClickHouse-laravel

Laravel adapter for PHP ClickHouse tooling:

- https://github.com/smi2/phpClickHouse — HTTP transport and query execution
- https://github.com/oralunal/clickhouse-builder — fluent query builder
  (fork of `glushkovds/ClickhouseBuilder`, itself a fork of
  `the-tinderbox/ClickhouseBuilder`)

## Features

- Eloquent-flavored `BaseModel` (`create`, `save`, `insertBulk`, `insertAssoc`, `where`, pagination)
- `PhpClickHouseLaravel\Migration` base class for ClickHouse DDL migrations (single-node and cluster)
- Query builder integration with `settings()`, `chunk()`, and ClickHouse-specific grammar
- Column casts (currently `boolean`) applied on insert
- Model events: `creating`, `created`, `saved`
- Retry-on-network-error support (`retries` config key)
- Buffer engine support via `$tableForInserts` / `$tableSources`
- `OPTIMIZE`, `TRUNCATE`, `ALTER TABLE ... DELETE`, `ALTER TABLE ... UPDATE` helpers
- Multi-instance and cluster-mode connections with active-node rotation
- Publishable default config — `.env` is enough for most setups

Underneath, smi2/phpClickHouse handles HTTP transport (curl-only, no PDO).
More: https://github.com/smi2/phpClickHouse#features

## Prerequisites

- PHP 8.5+
- Laravel 13+
- ClickHouse server 24.x (older 20+ versions usually work but are no longer tested)

## Installation

**1.** Install via composer:

```sh
composer require oralunal/phpclickhouse-laravel
```

The service provider is registered automatically via Laravel package
auto-discovery. If you have auto-discovery disabled, add
`PhpClickHouseLaravel\ClickhouseServiceProvider::class` to
`bootstrap/providers.php` (Laravel 11+) or `config/app.php` (Laravel 10 and below).

**2.** Configure the connection.

The simplest setup — just set these in your `.env`:

```dotenv
CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_DATABASE=default
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=
# only if you use an https connection
CLICKHOUSE_HTTPS=true
```

The service provider merges sensible defaults into
`config('database.connections.clickhouse')` for you. No config edits needed
for a single-node setup.

If you want to customize defaults beyond what env vars cover, publish
the config:

```sh
php artisan vendor:publish --tag=clickhouse-config
```

That drops a `config/clickhouse.php` into your app. Alternatively, you can
define the connection yourself in `config/database.php`:

```php
'clickhouse' => [
    'driver' => 'clickhouse',
    'host' => env('CLICKHOUSE_HOST'),
    'port' => env('CLICKHOUSE_PORT', '8123'),
    'database' => env('CLICKHOUSE_DATABASE', 'default'),
    'username' => env('CLICKHOUSE_USERNAME', 'default'),
    'password' => env('CLICKHOUSE_PASSWORD', ''),
    'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT', 2),
    'timeout_query' => env('CLICKHOUSE_TIMEOUT_QUERY', 2),
    'https' => (bool) env('CLICKHOUSE_HTTPS', null),
    'retries' => env('CLICKHOUSE_RETRIES', 0),
    'settings' => [ // optional
        'max_partitions_per_insert_block' => 300,
    ],
    'fix_default_query_builder' => true,
],
```

## Usage

You can use smi2/phpClickHouse directly:

```php
/** @var \ClickHouseDB\Client $db */
$db = DB::connection('clickhouse')->getClient();
$statement = $db->select('SELECT * FROM summing_url_views LIMIT 2');
```

More about `$db`: https://github.com/smi2/phpClickHouse/blob/master/README.md

#### Or use the Eloquent-like ORM

**1.** Add a model:

```php
<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable extends BaseModel
{
    // Optional. Derived from class name MyTable => my_table when omitted.
    protected $table = 'my_table';
}
```

**2.** Add a migration:

```php
<?php

class CreateMyTable extends \PhpClickHouseLaravel\Migration
{
    public function up()
    {
        static::write('
            CREATE TABLE my_table (
                id UInt32,
                created_at DateTime,
                field_one String,
                field_two Int32
            )
            ENGINE = MergeTree()
            ORDER BY (id)
        ');
    }

    public function down()
    {
        static::write('DROP TABLE my_table');
    }
}
```

Or use the Schema Builder:

```php
<?php

class CreateMyTable extends \PhpClickHouseLaravel\Migration
{
    public function up()
    {
        static::createMergeTree('my_table', fn(MergeTree $table) => $table
            ->columns([
                $table->uInt32('id'),
                $table->datetime('created_at', 3)->default(new Expression('now64()')),
                $table->string('field_one'),
                $table->int32('field_two'),
            ])
            ->orderBy('id')
        );
    }

    public function down()
    {
        static::write('DROP TABLE my_table');
    }
}
```

**3.** Insert data.

One row:

```php
$model = MyTable::create(['model_name' => 'model 1', 'some_param' => 1]);
# or
$model = MyTable::make(['model_name' => 'model 1']);
$model->some_param = 1;
$model->save();
# or
$model = new MyTable();
$model->fill(['model_name' => 'model 1', 'some_param' => 1])->save();
```

Bulk insert:

```php
# Non-assoc
MyTable::insertBulk([['model 1', 1], ['model 2', 2]], ['model_name', 'some_param']);
# Assoc
MyTable::insertAssoc([['model_name' => 'model 1', 'some_param' => 1], ['some_param' => 2, 'model_name' => 'model 2']]);
```

**4.** Query builder:

```php
$rows = MyTable::select(['field_one', new RawColumn('sum(field_two)', 'field_two_sum')])
    ->where('created_at', '>', '2020-09-14 12:47:29')
    ->groupBy('field_one')
    ->settings(['max_threads' => 3])
    ->getRows();
```

## Known issues

[Some of the problems are described here](/docs/known_issues.md).

## Advanced usage

### Columns casting

Before insertion, the column is converted to the data type specified in
`$casts`. This only applies to inserts, not selects. Supported: `boolean`.

```php
namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable extends BaseModel
{
    /**
     * The columns that should be cast.
     *
     * @var array
     */
    protected $casts = ['some_bool_column' => 'boolean'];
}
// Then you can insert the data like this:
MyTable::insertAssoc([
    ['some_param' => 1, 'some_bool_column' => false],
]);
```

### Events

Events work like [Eloquent model events](https://laravel.com/docs/eloquent#events).
Available: **creating**, **created**, **saved**.

### Retries

You can retry requests on non-200 responses (e.g., transient network errors).

In `.env`:

```dotenv
CLICKHOUSE_RETRIES=2
```

`retries` is optional; default is `0` (a single attempt, no retries). `1`
means one attempt + one retry on error (two total).

### Working with huge rows

Chunk results like in Laravel:

```php
// Split the result into chunks of 30 rows
$rows = MyTable::select(['field_one', 'field_two'])
    ->chunk(30, function ($rows) {
        foreach ($rows as $row) {
            echo $row['field_two'] . "\n";
        }
    });
```

### Buffer engine for insert queries

See https://clickhouse.tech/docs/en/engines/table-engines/special/buffer/

```php
<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable extends BaseModel
{
    // Optional; derived from class name when omitted.
    protected $table = 'my_table';
    // All inserts go to $tableForInserts, selects read from $table.
    protected $tableForInserts = 'my_table_buffer';
}
```

If you also want to read from the buffer table, set its name as `$table`:

```php
<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable extends BaseModel
{
    protected $table = 'my_table_buffer';
}
```

### OPTIMIZE Statement

See https://clickhouse.com/docs/en/sql-reference/statements/optimize/

```php
MyTable::optimize($final = false, $partition = null);
```

### TRUNCATE Statement

Remove all data from a table:

```php
MyTable::truncate();
```

### Deletions

See https://clickhouse.com/docs/en/sql-reference/statements/alter/delete/

```php
MyTable::where('field_one', 123)->delete();
```

Using the buffer engine with OPTIMIZE / ALTER TABLE DELETE:

```php
<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable extends BaseModel
{
    // SELECT and INSERT on $table
    protected $table = 'my_table_buffer';
    // OPTIMIZE and DELETE on $tableSources
    protected $tableSources = 'my_table';
}
```

### Updates

See https://clickhouse.com/docs/en/sql-reference/statements/alter/update/

```php
MyTable::where('field_one', 123)->update(['field_two' => 'new_val']);
// or an expression
MyTable::where('field_one', 123)
    ->update(['field_two' => new RawColumn("concat(field_two,'new_val')")]);
```

### Helpers for inserting different data types

```php
// Array data type
MyTable::insertAssoc([[1, 'str', new InsertArray(['a','b'])]]);
```

### Working with multiple ClickHouse instances in a project

`config/clickhouse.php` is a map from connection name to connection
config. The service provider merges every entry into
`config('database.connections.<name>')`, so you can declare additional
ClickHouse connections alongside the default one in a single file.

**1.** Publish the config if you haven't already:

```sh
php artisan vendor:publish --tag=clickhouse-config
```

Then add a second connection in `config/clickhouse.php`:

```php
return [
    'clickhouse' => [
        // ... default connection
    ],

    'clickhouse2' => [
        'driver' => 'clickhouse',
        'host' => env('CLICKHOUSE2_HOST', '127.0.0.1'),
        'port' => env('CLICKHOUSE2_PORT', '8123'),
        'database' => 'default',
        'username' => 'default',
        'password' => '',
        'timeout_connect' => 2,
        'timeout_query' => 2,
        'https' => false,
        'retries' => 0,
        'fix_default_query_builder' => true,
    ],
];
```

(Adding the same shape to `config/database.php`'s `connections` array
still works — user-supplied values always win over the package defaults.)

**2.** Add a model pointing at it:

```php
<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable2 extends BaseModel
{
    protected $connection = 'clickhouse2';

    protected $table = 'my_table2';
}
```

**3.** Add a migration bound to that connection:

```php
<?php

return new class extends \PhpClickHouseLaravel\Migration
{
    protected $connection = 'clickhouse2';

    public function up()
    {
        static::write('CREATE TABLE my_table2 ...');
    }

    public function down()
    {
        static::write('DROP TABLE my_table2');
    }
};
```

### Cluster mode

**Important!**
* Each ClickHouse node must share the same database name, username, and password.
* Reads and writes go to the first reachable node.
* Migrations execute on all nodes. If any node is unreachable, the migration throws.
* `ReplicatedMergeTree` uses the `{replica}` and `{shard}` macros — those
  must be defined on each ClickHouse server (in `config.xml` or
  `config.d/*.xml`), **not** in this package. Example config:
  ```xml
  <macros>
      <shard>01</shard>
      <replica>clickhouse01</replica>
  </macros>
  ```
  See `tests/docker/clickhouse01/config.xml` in this repo for a working
  example, or the ClickHouse docs:
  https://clickhouse.com/docs/en/operations/settings/settings#server_settings-macros

Your `config/database.php` should look like:

```php
'clickhouse' => [
    'driver' => 'clickhouse',
    'cluster' => [
        [
            'host' => 'clickhouse01',
            'port' => '8123',
        ],
        [
            'host' => 'clickhouse02',
            'port' => '8123',
        ],
    ],
    'database' => env('CLICKHOUSE_DATABASE', 'default'),
    'username' => env('CLICKHOUSE_USERNAME', 'default'),
    'password' => env('CLICKHOUSE_PASSWORD', ''),
    'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT', 2),
    'timeout_query' => env('CLICKHOUSE_TIMEOUT_QUERY', 2),
    'https' => (bool) env('CLICKHOUSE_HTTPS', null),
    'retries' => env('CLICKHOUSE_RETRIES', 0),
    'settings' => [ // optional
        'max_partitions_per_insert_block' => 300,
    ],
    'fix_default_query_builder' => true,
],
```

Migration:

```php
<?php

return new class extends \PhpClickHouseLaravel\Migration
{
    public function up()
    {
        static::write("
            CREATE TABLE my_table (
                id UInt32,
                created_at DateTime,
                field_one String,
                field_two Int32
            )
            ENGINE = ReplicatedMergeTree('/clickhouse/tables/default.my_table', '{replica}')
            ORDER BY (id)
        ");
    }

    public function down()
    {
        static::write('DROP TABLE my_table');
    }
};
```

You can read the current node and rotate to the next:

```php
$row = new MyTable();
echo $row->getThisClient()->getConnectHost();
// will print 'clickhouse01'
$row->resolveConnection()->getCluster()->slideNode();
echo $row->getThisClient()->getConnectHost();
// will print 'clickhouse02'
```

## Contributing

The package is developed against **Orchestra Testbench** with a local
ClickHouse in Docker. To run the test suite locally:

1. `docker compose -f docker-compose.test.yaml up -d`
2. `composer install`
3. `composer test`

See [docs/howto_run_local_test.md](docs/howto_run_local_test.md) for
prerequisites, cluster-test notes, and using `vendor/bin/testbench` /
Laravel Boost during development.
