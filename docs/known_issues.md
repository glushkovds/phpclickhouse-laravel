## Parameter numbering with whereIn

The problem is described here: https://github.com/glushkovds/phpclickhouse-laravel/pull/34

For `DB::table('my-table')` the default builder used to be
`\Illuminate\Database\Query\Builder` instead of this library's
`\PhpClickHouseLaravel\Builder`, which broke parameter numbering for
`whereIn` / `whereBetween`.

### Status

`fix_default_query_builder` is now **enabled by default** in the
shipped `config/clickhouse.php`, so most users do not need to do
anything. The rest of this page is for users who define the
connection manually in `config/database.php` and opted out.

### Opting in manually

If you maintain your own connection block in `config/database.php`,
make sure it contains:

```php
'clickhouse' => [
    'driver' => 'clickhouse',
    // ... your other keys
    'fix_default_query_builder' => true,
],
```

### Breaking changes

The fix changes the return type of `DB::table('my-table')->...->get()`
from an Eloquent collection to a ClickHouse `Statement`. If you rely
on the old behavior:

```php
$rows = DB::table('my-table')
    ...
    ->get(); // was Collection
```

switch to:

```php
$rows = DB::table('my-table')
    ...
    ->get()   // Statement
    ->rows(); // array
```

### Why not use `\Illuminate\Database\Query\Builder`

This library uses [oralunal/clickhouse-builder](https://github.com/oralunal/clickhouse-builder)
(a fork of `glushkovds/ClickhouseBuilder`, itself a fork of
`the-tinderbox/ClickhouseBuilder`), which provides its own builder
with ClickHouse-specific methods that the standard builder cannot
represent, and vice versa.

