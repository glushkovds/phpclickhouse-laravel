![Tests](https://github.com/glushkovds/phpclickhouse-laravel/actions/workflows/test.yml/badge.svg)

# phpClickHouse-laravel

Adapter to Laravel and Lumen of the most popular libraries:

- https://github.com/smi2/phpClickHouse - for connections and perform queries
- https://github.com/the-tinderbox/ClickhouseBuilder - good query builder

## Features

No dependency, only Curl (support php >=8.0 )

More: https://github.com/smi2/phpClickHouse#features

## Prerequisites

- PHP 8.0
- Laravel/Lumen 7+
- Clickhouse server

## Installation

**1.**  Install via composer

```sh
$ composer require glushkovds/phpclickhouse-laravel
```

**2.** Add new connection into your config/database.php:

```php
'clickhouse' => [
    'driver' => 'clickhouse',
    'host' => env('CLICKHOUSE_HOST'),
    'port' => env('CLICKHOUSE_PORT','8123'),
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
],
```

Then patch your .env:

```dotenv
CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_DATABASE=default
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=
CLICKHOUSE_TIMEOUT_CONNECT=2
CLICKHOUSE_TIMEOUT_QUERY=2
# only if you use https connection
CLICKHOUSE_HTTPS=true
```

**3.** Add service provider into your config/app.php (bootstrap/providers.php for Laravel 11+) file providers section:

```php
\PhpClickHouseLaravel\ClickhouseServiceProvider::class,
```
It should be placed *before* App\Providers\AppServiceProvider::class, and   App\Providers\EventServiceProvider::class.

## Usage

You can use smi2/phpClickHouse functionality directly:

```php
/** @var \ClickHouseDB\Client $db */
$db = DB::connection('clickhouse')->getClient();
$statement = $db->select('SELECT * FROM summing_url_views LIMIT 2');
```

More about `$db` see here: https://github.com/smi2/phpClickHouse/blob/master/README.md

#### Or use dawnings of Eloquent ORM (will be implemented completely)

**1.** Add model

```php
<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable extends BaseModel
{
    // Not necessary. Can be obtained from class name MyTable => my_table
    protected $table = 'my_table';

}
```

**2.** Add migration

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

**3.** And then you can insert data

One row

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

Or bulk insert

```php
# Non assoc way
MyTable::insertBulk([['model 1', 1], ['model 2', 2]], ['model_name', 'some_param']);
# Assoc way
MyTable::insertAssoc([['model_name' => 'model 1', 'some_param' => 1], ['some_param' => 2, 'model_name' => 'model 2']]);
```

**4.** Now check out the query builder

```php
$rows = MyTable::select(['field_one', new RawColumn('sum(field_two)', 'field_two_sum')])
    ->where('created_at', '>', '2020-09-14 12:47:29')
    ->groupBy('field_one')
    ->settings(['max_threads' => 3])
    ->getRows();
```

## Advanced usage

### Columns casting

Before insertion, the column will be converted to the required data type specified in the field `$casts`.  
This feature does not apply to data selection.  
The supported cast types are: `boolean`.

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

Events work just like an [eloquent model events](https://laravel.com/docs/9.x/eloquent#events)  
Available events: **creating**, **created**, **saved**

### Retries

You may enable ability to retry requests while received not 200 response, maybe due network connectivity problems.

Patch your .env:

```dotenv
CLICKHOUSE_RETRIES=2
```

retries is optional, default value is 0.  
0 mean only one attempt.  
1 mean one attempt + 1 retry while error (total 2 attempts).

### Working with huge rows

You can chunk results like in Laravel

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
    // Not necessary. Can be obtained from class name MyTable => my_table
    protected $table = 'my_table';
    // All inserts will be in the table $tableForInserts 
    // But all selects will be from $table
    protected $tableForInserts = 'my_table_buffer';
}
```

If you also want to read from your buffer table, put its name in $table

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

Using buffer engine and performing OPTIMIZE or ALTER TABLE DELETE

```php
<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;

class MyTable extends BaseModel
{
    // All SELECT's and INSERT's on $table
    protected $table = 'my_table_buffer';
    // OPTIMIZE and DELETE on $tableSources
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

### Working with multiple Clickhouse instances in a project

**1.** Add second connection into your config/database.php:

```php
'clickhouse2' => [
    'driver' => 'clickhouse',
    'host' => 'clickhouse2',
    'port' => '8123',
    'database' => 'default',
    'username' => 'default',
    'password' => '',
    'timeout_connect' => 2,
    'timeout_query' => 2,
    'https' => false,
    'retries' => 0,
],
```

**2.** Add model

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
**3.** Add migration

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
* Each ClickHouse node must have one database name and login and password.
* For reading and writing, the connection is made to the first available node.
* Migrations executes on all nodes. If one of the nodes is unavailable, the migration will throw an exception.

Your config/database.php should look like:
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
],
```

Migration is:

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

You can get the host of the current node and switch the active connection to the next node:
```php
$row = new MyTable();
echo $row->getThisClient()->getConnectHost();
// will print 'clickhouse01'
$row->resolveConnection()->getCluster()->slideNode();
echo $row->getThisClient()->getConnectHost();
// will print 'clickhouse02'
```