## Parameter numbering with whereIn

The problem is described here: https://github.com/glushkovds/phpclickhouse-laravel/pull/34

The reason for the problem is that for the `DB::table('my-table')` construction 
the builder used was not from this library `\PhpClickHouseLaravel\Builder`, 
but the standard `\Illuminate\Database\Query\Builder`.

### To fix this problem

Add `'fix_default_query_builder' => true,` to connection config in `config/database.php` like this:

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
    'fix_default_query_builder' => true,
],
```

### Breaking changes

This fix will break your existing code if you use constructs like this:
```php
$rows = DB::table('my-table')
    ...
    ->get(); // Collection
```
You need to change your code to:
```php
$rows = DB::table('my-table')
    ...
    ->get() // Statement
    ->rows(); // array
```

### Why not use \Illuminate\Database\Query\Builder

Because this library use [the-tinderbox/ClickhouseBuilder](https://github.com/the-tinderbox/ClickhouseBuilder), 
which offers its own builder.  
It offers special methods for working with Clickhouse, which are not available in the standard builder.  
And vice versa, the standard builder has methods that cannot be implemented for Clickhouse.

### In the feature release v3
In the feature release v3 this fix will be applied by default.

