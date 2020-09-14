# phpClickHouse-laravel
Adapter of the most popular library https://github.com/smi2/phpClickHouse to Laravel

## Prerequisites
- php 7.1
- clickhouse server

## Installation
Now available only dev version

**1.** Add to composer.json:
```json
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/glushkovds/phpClickHouse-laravel.git"
    }
]
```
And then:
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
                id Int32
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

**3.** And then use it
```php
MyAwesomeModel::insert($rows);
```