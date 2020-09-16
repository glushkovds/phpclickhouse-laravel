# phpClickHouse-laravel

Adapter to Laravel of the most popular libraries:
- https://github.com/smi2/phpClickHouse - for connections and perform queries
- https://github.com/the-tinderbox/ClickhouseBuilder - good query builder

## Features

No dependency, only Curl (support php >=7.1 )

More: https://github.com/smi2/phpClickHouse#features
    
## Prerequisites
- php 7.1
- Laravel 7+
- clickhouse server

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
]
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
```

**3.** Add service provider into your config/app.php file providers section:
```php
\PhpClickHouseLaravel\ClickhouseServiceProvider::class,
```

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

class MyTableModel extends BaseModel
{
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

**3.** And then you can insert one row or bulk insert
```php
MyAwesomeModel::insert($rows);
```

**4.** Now check out the query builder 
```php
$rows = MyAwesomeModel::select(['field_one', new RawColumn('sum(field_two)', 'field_two_sum')])
    ->where('created_at', '>', '2020-09-14 12:47:29')
    ->groupBy('field_one')
    ->getRows();
```