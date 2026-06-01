![Tests](https://github.com/glushkovds/phpclickhouse-laravel/actions/workflows/test.yml/badge.svg)

# phpClickHouse-laravel

Laravel and Lumen adapter for popular ClickHouse libraries:

- https://github.com/smi2/phpClickHouse - connections and query execution
- https://github.com/the-tinderbox/ClickhouseBuilder - query builder

## Features

Requires only the PHP cURL extension. PHP 8.0 or higher is supported.

See phpClickHouse features for more details: https://github.com/smi2/phpClickHouse#features

## Prerequisites

- PHP 8.0
- Laravel/Lumen 7+
- ClickHouse server

## Installation

Install via Composer:

```sh
$ composer require glushkovds/phpclickhouse-laravel
```

Laravel will discover the package service provider automatically.

The package registers a `clickhouse` database connection with sensible defaults. Set only the environment values you need to override:

```dotenv
CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_DATABASE=default
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=
CLICKHOUSE_TIMEOUT_CONNECT=2
CLICKHOUSE_TIMEOUT_QUERY=2
CLICKHOUSE_HTTPS=false
CLICKHOUSE_RETRIES=0
CLICKHOUSE_MAX_PARTITIONS_PER_INSERT_BLOCK=300
CLICKHOUSE_FIX_DEFAULT_QUERY_BUILDER=true
```

If your application uses cached configuration, rebuild the cache after changing ClickHouse environment values.

The package registers this connection as `database.connections.clickhouse` automatically when the application does not already define it in `config/database.php`.  
If you need to override the defaults or add advanced options, define the connection in `config/database.php`:

```php
'connections' => [
    'clickhouse' => [
        'driver' => 'clickhouse',
        'host' => env('CLICKHOUSE_HOST'),
        'port' => (int) env('CLICKHOUSE_PORT', 8123),
        'database' => env('CLICKHOUSE_DATABASE', 'default'),
        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', ''),
        'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT', 2),
        'timeout_query' => env('CLICKHOUSE_TIMEOUT_QUERY', 2),
        'https' => (bool) env('CLICKHOUSE_HTTPS', false),
        'retries' => env('CLICKHOUSE_RETRIES', 0),
        'settings' => [
            'max_partitions_per_insert_block' => 300,
        ],
        'fix_default_query_builder' => true,
    ],
],
```

If you use Lumen or have disabled Laravel package discovery, register the service provider manually:

```php
$app->register(\PhpClickHouseLaravel\ClickhouseServiceProvider::class);
```

## Usage

You can use smi2/phpClickHouse functionality directly:

```php
/** @var \ClickHouseDB\Client $db */
$db = DB::connection('clickhouse')->getClient();
$statement = $db->select('SELECT * FROM summing_url_views LIMIT 2');
```

See the phpClickHouse documentation for more details: https://github.com/smi2/phpClickHouse/blob/master/README.md

### Lightweight Eloquent-like model layer

**1.** Add a model

```php
<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable extends BaseModel
{
    // Optional. By default, MyTable resolves to my_table.
    protected $table = 'my_table';

}
```

**2.** Add a migration

```php
<?php

class CreateMyTable extends \PhpClickHouseLaravel\Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        static::write('DROP TABLE my_table');
    }
}
```

You can also use the Schema Builder:

```php
<?php

class CreateMyTable extends \PhpClickHouseLaravel\Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        static::write('DROP TABLE my_table');
    }
}
```

**3.** Insert data

Single row

```php
$model = MyTable::create(['model_name' => 'model 1', 'some_param' => 1]);
// or
$model = MyTable::make(['model_name' => 'model 1']);
$model->some_param = 1;
$model->save();
// or
$model = new MyTable();
$model->fill(['model_name' => 'model 1', 'some_param' => 1])->save();
```

Bulk insert

```php
// Non-associative way
MyTable::insertBulk([['model 1', 1], ['model 2', 2]], ['model_name', 'some_param']);
// Associative way
MyTable::insertAssoc([['model_name' => 'model 1', 'some_param' => 1], ['some_param' => 2, 'model_name' => 'model 2']]);
```

**4.** Query data

```php
$rows = MyTable::select(['field_one', new RawColumn('sum(field_two)', 'field_two_sum')])
    ->where('created_at', '>', '2020-09-14 12:47:29')
    ->groupBy('field_one')
    ->settings(['max_threads' => 3])
    ->getRows();
```

## Known issues

Known issues are described in [docs/known_issues.md](/docs/known_issues.md).

## Advanced usage

### Column casting

Before insertion, columns are converted to the data types defined in `$casts`.  
This feature does not apply to data selection.  
Supported cast types: `boolean`.

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

Events work like [Eloquent model events](https://laravel.com/docs/9.x/eloquent#events).  
Available events: **creating**, **created**, **saved**.

### Retries

You may enable request retries for non-200 responses, for example when temporary network issues occur.

Add the value to your `.env` file:

```dotenv
CLICKHOUSE_RETRIES=2
```

`CLICKHOUSE_RETRIES` is optional. The default value is `0`.  
`0` means one attempt.  
`1` means one attempt plus one retry on error, for a total of two attempts.

### Working with huge rows

You can chunk results like in Laravel:

```php
// Split the result into chunks of 30 rows.
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
    // Optional. By default, MyTable resolves to my_table.
    protected $table = 'my_table';

    // Inserts use $tableForInserts, selects use $table.
    protected $tableForInserts = 'my_table_buffer';
}
```

If you also want to read from the buffer table, set `$table` to the buffer table name:

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

See https://clickhouse.com/docs/ru/sql-reference/statements/optimize/

```php
MyTable::optimize($final = false, $partition = null);
```

### TRUNCATE Statement

Removes all data from a table.

```php
MyTable::truncate();
```

### Deletions

See https://clickhouse.com/docs/en/sql-reference/statements/alter/delete/

```php
MyTable::where('field_one', 123)->delete();
```

When using the Buffer engine, `OPTIMIZE` and `ALTER TABLE DELETE` can target the source table:

```php
<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable extends BaseModel
{
    // SELECT and INSERT queries use $table.
    protected $table = 'my_table_buffer';

    // OPTIMIZE and DELETE queries use $tableSources.
    protected $tableSources = 'my_table';
}
```

### Updates

See https://clickhouse.com/docs/ru/sql-reference/statements/alter/update/

```php
MyTable::where('field_one', 123)->update(['field_two' => 'new_val']);
// or expression
MyTable::where('field_one', 123)
    ->update(['field_two' => new RawColumn("concat(field_two,'new_val')")]);
```

### Helpers for inserting different data types

```php
// Array data type
MyTable::insertAssoc([[1, 'str', new InsertArray(['a','b'])]]);
```

### Working with multiple ClickHouse instances in a project

**1.** Add a second connection to `config/database.php`:

```php
'clickhouse2' => [
    'driver' => 'clickhouse',
    'host' => 'clickhouse2',
    'port' => 8123,
    'database' => 'default',
    'username' => 'default',
    'password' => '',
    'timeout_connect' => 2,
    'timeout_query' => 2,
    'https' => false,
    'retries' => 0,
    'fix_default_query_builder' => true,
],
```

**2.** Add a model

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
**3.** Add a migration

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
* Each ClickHouse node must use the same database name, username, and password.
* Reads and writes use the first available node.
* Migrations run on all nodes. If one node is unavailable, the migration will throw an exception.

Your `config/database.php` should look like this:
```php
'clickhouse' => [
    'driver' => 'clickhouse',
    'cluster' => [
        [
            'host' => 'clickhouse01',
            'port' => 8123,
        ],
        [
            'host' => 'clickhouse02',
            'port' => 8123,
        ],
    ],
    'database' => env('CLICKHOUSE_DATABASE','default'),
    'username' => env('CLICKHOUSE_USERNAME','default'),
    'password' => env('CLICKHOUSE_PASSWORD',''),
    'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT',2),
    'timeout_query' => env('CLICKHOUSE_TIMEOUT_QUERY',2),
    'https' => (bool)env('CLICKHOUSE_HTTPS', null),
    'retries' => env('CLICKHOUSE_RETRIES', 0),
    'settings' => [ // optional
        'max_partitions_per_insert_block' => 300,
    ],
    'fix_default_query_builder' => true,
],
```

Migration example:

```php
<?php

return new class extends \PhpClickHouseLaravel\Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        static::write('
            CREATE TABLE my_table (
                id UInt32,
                created_at DateTime,
                field_one String,
                field_two Int32
            )
            ENGINE = ReplicatedMergeTree('/clickhouse/tables/default.my_table', '{replica}')
            ORDER BY (id)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        static::write('DROP TABLE my_table');
    }
};
```

You can get the current node host and switch the active connection to the next node:
```php
$row = new MyTable();
echo $row->getThisClient()->getConnectHost();
// will print 'clickhouse01'
$row->resolveConnection()->getCluster()->slideNode();
echo $row->getThisClient()->getConnectHost();
// will print 'clickhouse02'
```
