# Testbench-Based Package Development Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert `oralunal/phpclickhouse-laravel` from a "copy-into-a-real-Laravel-app" test flow to a modern Laravel package setup based on **Orchestra Testbench**, so that `vendor/bin/testbench` works in the repo root, Laravel Boost can attach to the package via a `workbench/` skeleton, and the test suite runs directly against a ClickHouse container without the Docker-copy-into-bitnami-laravel dance.

**Architecture:** Replace the `tests/CreatesApplication.php` + `tests.bootstrap.sh` bootstrap (which relies on `bootstrap/app.php` that only exists inside `bitnami/laravel`) with `Orchestra\Testbench\TestCase`. The service provider becomes discoverable via `extra.laravel.providers` and publishes a real `config/clickhouse.php`. Fixture migrations stay in the package under `tests/migrations/` and get registered via `loadMigrationsFrom()` at runtime. `docker-compose.test.yaml` is reduced to only run ClickHouse; PHP runs on the host (or a minimal php container) via Composer.

**Tech Stack:**
- PHP 8.5 / Laravel 13
- `orchestra/testbench` (matches Laravel 13 — v10.x)
- PHPUnit 11 (bundled with Testbench / Laravel 13)
- `laravel/boost` (dev, via workbench)
- ClickHouse 24.8 (unchanged from current docker-compose)

**Out of scope:**
- Switching from PHPUnit to Pest (keep PHPUnit for minimum churn).
- Rewriting the ClickHouse Grammar/Builder classes.
- Publishing to Packagist — this is local dev infra only.

---

## Chunk 1: Composer Setup & Package Discovery

### Task 1: Add dev dependencies & auto-discovery to `composer.json`

**Files:**
- Modify: `composer.json`

- [ ] **Step 1.1: Edit composer.json**

Add a `require-dev` block, an `extra.laravel.providers` entry for auto-discovery, and composer scripts. Final shape:

```json
{
  "name": "oralunal/phpclickhouse-laravel",
  "description": "Adapter of the most popular library https://github.com/smi2/phpClickHouse to Laravel",
  "keywords": ["php", "laravel", "clickhouse"],
  "homepage": "https://github.com/oralunal/phpclickhouse-laravel",
  "type": "library",
  "license": "MIT",
  "authors": [
    { "name": "Denis Glushkov", "email": "amkarovec@gmail.com", "homepage": "https://github.com/glushkovds" },
    { "name": "Oral Unal", "email": "oralunal@gmail.com", "homepage": "https://github.com/oralunal" }
  ],
  "require": {
    "php": "^8.5",
    "smi2/phpclickhouse": "^1.26",
    "oralunal/clickhouse-builder": "^1",
    "laravel/framework": "^13",
    "glushkovds/php-clickhouse-schema-builder": "^1.1"
  },
  "require-dev": {
    "orchestra/testbench": "^10.0",
    "phpunit/phpunit": "^11.0",
    "laravel/boost": "^1.0"
  },
  "autoload": {
    "psr-4": { "PhpClickHouseLaravel\\": "src/" }
  },
  "autoload-dev": {
    "psr-4": { "Tests\\": "tests/" }
  },
  "extra": {
    "laravel": {
      "providers": [
        "PhpClickHouseLaravel\\ClickhouseServiceProvider"
      ]
    }
  },
  "scripts": {
    "post-autoload-dump": [
      "@php vendor/bin/testbench package:discover --ansi"
    ],
    "clear": [
      "@php vendor/bin/testbench package:purge-skeleton --ansi",
      "@php vendor/bin/testbench package:discover --ansi"
    ],
    "test": "vendor/bin/phpunit",
    "boost": "@php vendor/bin/testbench boost:install"
  },
  "config": {
    "sort-packages": true,
    "allow-plugins": {
      "pestphp/pest-plugin": true,
      "php-http/discovery": true
    }
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

- [ ] **Step 1.2: Install dependencies**

Run: `composer install`
Expected: `orchestra/testbench`, `laravel/boost`, `phpunit/phpunit` resolve and install; `package:discover` prints `PhpClickHouseLaravel\\ClickhouseServiceProvider`.

If `composer install` fails because of version constraint conflicts, run `composer update` instead and then commit the resulting `composer.lock`.

- [ ] **Step 1.3: Verify Testbench binary exists**

Run: `ls vendor/bin/testbench && vendor/bin/testbench --version`
Expected: Testbench prints its version banner (Laravel 13 + Testbench 10.x).

- [ ] **Step 1.4: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: add orchestra/testbench and laravel/boost dev deps, enable package auto-discovery"
```

---

### Task 2: Publishable config file

**Files:**
- Create: `config/clickhouse.php`

- [ ] **Step 2.1: Write `config/clickhouse.php`**

This file becomes the default config values merged into `config('database.connections.clickhouse')` when consumers do not override them. Content:

```php
<?php

return [
    'driver'          => 'clickhouse',
    'host'            => env('CLICKHOUSE_HOST', '127.0.0.1'),
    'port'            => env('CLICKHOUSE_PORT', '8123'),
    'database'        => env('CLICKHOUSE_DATABASE', 'default'),
    'username'        => env('CLICKHOUSE_USERNAME', 'default'),
    'password'        => env('CLICKHOUSE_PASSWORD', ''),
    'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT', 2),
    'timeout_query'   => env('CLICKHOUSE_TIMEOUT_QUERY', 2),
    'https'           => (bool) env('CLICKHOUSE_HTTPS', false),
    'retries'         => env('CLICKHOUSE_RETRIES', 0),
    'settings'        => [],
    'fix_default_query_builder' => true,
];
```

- [ ] **Step 2.2: Commit**

```bash
git add config/clickhouse.php
git commit -m "feat: add publishable clickhouse config"
```

---

### Task 3: Extend `ClickhouseServiceProvider` with `register()` + `publishes()`

**Files:**
- Modify: `src/ClickhouseServiceProvider.php`

- [ ] **Step 3.1: Add register()/publish logic**

Replace the file with:

```php
<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider to connect Clickhouse driver in Laravel.
 */
class ClickhouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/clickhouse.php', 'clickhouse');
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/clickhouse.php' => function_exists('config_path')
                ? config_path('clickhouse.php')
                : base_path('config/clickhouse.php'),
        ], 'clickhouse-config');

        $db = $this->app->make('db');

        $db->extend('clickhouse', function ($config, $name) {
            $config['name'] = $name;

            return Connection::createWithClient($config);
        });

        BaseModel::setEventDispatcher($this->app['events']);
    }
}
```

- [ ] **Step 3.2: Commit**

```bash
git add src/ClickhouseServiceProvider.php
git commit -m "feat(provider): merge default clickhouse config and publish config file"
```

---

## Chunk 2: Testbench-Based Test Bootstrapping

### Task 4: Replace `tests/TestCase.php` and drop `CreatesApplication.php`

**Files:**
- Modify: `tests/TestCase.php`
- Delete: `tests/CreatesApplication.php`
- Delete: `tests/config/app.php`
- Delete: `tests/config/database.php`

- [ ] **Step 4.1: Rewrite `tests/TestCase.php`**

```php
<?php

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PhpClickHouseLaravel\ClickhouseServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ClickhouseServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('database.default', 'clickhouse');

        $config->set('database.connections.clickhouse', [
            'driver'          => 'clickhouse',
            'host'            => env('CLICKHOUSE_HOST', '127.0.0.1'),
            'port'            => env('CLICKHOUSE_PORT', '18123'),
            'database'        => env('CLICKHOUSE_DATABASE', 'default'),
            'username'        => env('CLICKHOUSE_USERNAME', 'default'),
            'password'        => env('CLICKHOUSE_PASSWORD', ''),
            'timeout_connect' => 2,
            'timeout_query'   => 2,
            'https'           => false,
            'retries'         => 0,
            'settings'        => ['max_partitions_per_insert_block' => 300],
            'fix_default_query_builder' => true,
        ]);

        $config->set('database.connections.clickhouse2', [
            'driver'   => 'clickhouse',
            'host'     => env('CLICKHOUSE2_HOST', '127.0.0.1'),
            'port'     => env('CLICKHOUSE2_PORT', '18124'),
            'database' => 'default',
            'username' => 'default',
            'password' => '',
            'timeout_connect' => 2,
            'timeout_query'   => 2,
            'https'    => false,
            'retries'  => 0,
        ]);

        $config->set('database.connections.clickhouse-cluster', [
            'driver'  => 'clickhouse',
            'cluster' => [
                ['host' => env('CLICKHOUSE_HOST', '127.0.0.1'),  'port' => env('CLICKHOUSE_PORT', '18123')],
                ['host' => env('CLICKHOUSE2_HOST', '127.0.0.1'), 'port' => env('CLICKHOUSE2_PORT', '18124')],
            ],
            'cluster_name'   => 'company_cluster',
            'database'       => 'default',
            'username'       => 'default',
            'password'       => '',
            'timeout_connect'=> 2,
            'timeout_query'  => 2,
            'https'          => false,
            'retries'        => 0,
        ]);

        $config->set('database.connections.problem-clickhouse-cluster', [
            'driver'  => 'clickhouse',
            'cluster' => [
                ['host' => 'clickhouse-does-not-exist', 'port' => '8123'],
                ['host' => env('CLICKHOUSE2_HOST', '127.0.0.1'), 'port' => env('CLICKHOUSE2_PORT', '18124')],
            ],
            'database'       => 'default',
            'username'       => 'default',
            'password'       => '',
            'timeout_connect'=> 2,
            'timeout_query'  => 2,
            'https'          => false,
            'retries'        => 0,
        ]);
    }
}
```

- [ ] **Step 4.2: Delete obsolete files**

```bash
git rm tests/CreatesApplication.php tests/config/app.php tests/config/database.php
# remove the now-empty config directory if it has no .env file
rmdir tests/config 2>/dev/null || true
```

- [ ] **Step 4.3: Commit**

```bash
git add tests/TestCase.php
git commit -m "refactor(tests): bootstrap via Orchestra Testbench instead of copy-into-laravel"
```

---

### Task 5: Register test migrations via `loadMigrationsFrom`

**Files:**
- Modify: `tests/TestCase.php` (add migration loading)
- Modify: `tests/BaseTest.php::testPretendMigration` (adjust path)

- [ ] **Step 5.1: Add migration registration**

Append inside `TestCase::defineEnvironment()` (before closing brace), so fixture migrations are discoverable by `php artisan migrate`:

```php
        $app->afterResolving('migrator', function ($migrator) {
            $migrator->path(__DIR__ . '/migrations');
        });
```

> Why `afterResolving` instead of `loadMigrationsFrom()`: `loadMigrationsFrom` lives on `ServiceProvider`, not `TestCase`. `afterResolving` on the `migrator` container binding has the same effect.

- [ ] **Step 5.2: Rename fixture files to Laravel migration format**

Migrations must have a `YYYY_MM_DD_HHMMSS_*.php` prefix so Laravel's migrator sorts them correctly.

```bash
git mv tests/migrations/exampleTable.php   tests/migrations/2022_01_01_000000_create_examples_table.php
git mv tests/migrations/example2Table.php  tests/migrations/2022_01_01_000001_create_examples2_table.php
git mv tests/migrations/example3Table.php  tests/migrations/2022_01_01_000002_create_examples3_table.php
git mv tests/migrations/example4Table.php  tests/migrations/2022_01_01_000003_create_examples4_table.php
git mv tests/migrations/example5Table.php  tests/migrations/2022_01_01_000004_create_examples5_table.php
```

- [ ] **Step 5.3: Update `testPretendMigration` to use new path**

Open `tests/BaseTest.php`. The test currently does:

```php
$migration = file_get_contents(base_path('database/migrations/2022_01_01_000000_example.php'));
...
file_put_contents(base_path('database/migrations/example_' . rand(...) . '.php'), $migrationTmp);
```

Change both to the package-local path (the testbench skeleton does not ship the fixture):

```php
$migrationsDir = __DIR__ . '/migrations';
$migration     = file_get_contents($migrationsDir . '/2022_01_01_000000_create_examples_table.php');
...
file_put_contents($migrationsDir . '/example_' . rand(0, 9999999999999999) . '.php', $migrationTmp);
```

Also add a `tearDown()` on `BaseTest` to clean up the scratch migration files:

```php
protected function tearDown(): void
{
    foreach (glob(__DIR__ . '/migrations/example_*.php') as $f) {
        @unlink($f);
    }
    parent::tearDown();
}
```

- [ ] **Step 5.4: Commit**

```bash
git add tests/TestCase.php tests/migrations tests/BaseTest.php
git commit -m "refactor(tests): load fixture migrations from tests/migrations via testbench migrator"
```

---

### Task 6: Update `phpunit.xml` for PHPUnit 11 + env vars

**Files:**
- Modify: `phpunit.xml`

- [ ] **Step 6.1: Rewrite `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         failOnRisky="true"
         failOnWarning="true">
    <testsuites>
        <testsuite name="phpClickHouse-laravel test suite">
            <directory>tests</directory>
            <exclude>tests/Models</exclude>
            <exclude>tests/migrations</exclude>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
    <php>
        <env name="APP_KEY" value="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA="/>
        <env name="CLICKHOUSE_HOST" value="127.0.0.1"/>
        <env name="CLICKHOUSE_PORT" value="18123"/>
        <env name="CLICKHOUSE2_HOST" value="127.0.0.1"/>
        <env name="CLICKHOUSE2_PORT" value="18124"/>
        <env name="CLICKHOUSE_DATABASE" value="default"/>
        <env name="CLICKHOUSE_USERNAME" value="default"/>
        <env name="CLICKHOUSE_PASSWORD" value=""/>
    </php>
</phpunit>
```

- [ ] **Step 6.2: Add `.phpunit.cache` to `.gitignore`**

Open `.gitignore` and append:
```
.phpunit.cache/
```

- [ ] **Step 6.3: Commit**

```bash
git add phpunit.xml .gitignore
git commit -m "chore(tests): upgrade phpunit config to v11 schema and parameterize clickhouse host/port"
```

---

### Task 7: First green run against docker ClickHouse

**Files:**
- No code changes in this task — verification only.

- [ ] **Step 7.1: Spin up ClickHouse**

Run (in repo root):
```
docker compose -f docker-compose.test.yaml up -d clickhouse01 clickhouse02 zookeeper
```

Wait ~10s for ClickHouse to accept connections, then verify:
```
curl -s 'http://127.0.0.1:18123/?query=SELECT%201'
```
Expected: `1`.

- [ ] **Step 7.2: Run the suite**

Run: `vendor/bin/phpunit --testdox`
Expected: All BaseTest / MultiInstancesTest / BindingsTest / CastsTest / EventsTest / UpdateTest pass. ClusterTest may pass if `company_cluster` is defined in the ClickHouse server config (it is, per `tests/docker/clickhouse01`).

- [ ] **Step 7.3: If failures appear**

Do NOT silently patch tests. Invoke `superpowers:systematic-debugging` and work through the failure. Expected failure modes to investigate:
- ClickHouse not yet ready → increase sleep between `up -d` and test run.
- `Artisan::call('migrate')` not picking up fixtures → confirm `afterResolving('migrator', …)` ran (add a `dd(...)` temporarily in TestCase).
- `base_path()` in `testPretendMigration` still points to old path → confirm Step 5.3 was applied.

- [ ] **Step 7.4: Commit only if code changes were required to green the suite**

Otherwise move to Task 8 without a commit.

---

## Chunk 3: Docker Simplification & Retire Bootstrap Script

### Task 8: Slim down `docker-compose.test.yaml`

**Files:**
- Modify: `docker-compose.test.yaml`
- Delete: `tests/docker/Dockerfile`
- Delete: `tests.bootstrap.sh`

- [ ] **Step 8.1: Rewrite `docker-compose.test.yaml`** (drop the php service)

```yaml
services:

  clickhouse01:
    image: clickhouse/clickhouse-server:24.8
    ports:
      - "18123:8123"
    depends_on:
      - zookeeper
    volumes:
      - ./tests/docker/clickhouse01:/etc/clickhouse-server

  clickhouse02:
    image: clickhouse/clickhouse-server:24.8
    ports:
      - "18124:8123"
    depends_on:
      - zookeeper
    volumes:
      - ./tests/docker/clickhouse02:/etc/clickhouse-server

  zookeeper:
    image: zookeeper:3.7
```

- [ ] **Step 8.2: Remove obsolete files**

```bash
git rm tests/docker/Dockerfile tests.bootstrap.sh
```

- [ ] **Step 8.3: Update `docs/howto_run_local_test.md`**

Read the file first (`Read` tool), then replace its "run docker-compose ... and wait" steps with a new three-line flow:

```md
## Running tests locally

1. `docker compose -f docker-compose.test.yaml up -d`
2. `composer install`
3. `composer test` (or `vendor/bin/phpunit`)

To tear down: `docker compose -f docker-compose.test.yaml down -v`
```

- [ ] **Step 8.4: Commit**

```bash
git add docker-compose.test.yaml tests/docker docs/howto_run_local_test.md
git commit -m "chore: drop bitnami-laravel test container; run tests against clickhouse-only compose"
```

---

## Chunk 4: Laravel Boost via Workbench

### Task 9: Scaffold `workbench/` and wire Boost

**Files:**
- Create: `workbench/` (generated by testbench command)
- Modify: `composer.json` (already has `boost` script from Task 1)
- Modify: `.gitignore`

- [ ] **Step 9.1: Install workbench skeleton**

Run: `vendor/bin/testbench workbench:install`
Expected: creates `workbench/app/Providers/WorkbenchServiceProvider.php`, `workbench/database/`, `workbench/routes/`, and a `testbench.yaml` at the repo root.

- [ ] **Step 9.2: Configure `testbench.yaml`** to register the provider + ClickHouse env

```yaml
providers:
  - PhpClickHouseLaravel\ClickhouseServiceProvider
  - Workbench\App\Providers\WorkbenchServiceProvider

env:
  DB_CONNECTION: clickhouse
  CLICKHOUSE_HOST: 127.0.0.1
  CLICKHOUSE_PORT: 18123
```

- [ ] **Step 9.3: Smoke-test artisan**

Run:
```
vendor/bin/testbench list
vendor/bin/testbench tinker --execute="dump(config('database.connections.clickhouse'));"
```
Expected: artisan command list prints normally; tinker dumps the clickhouse connection config with host `127.0.0.1` and port `18123`.

- [ ] **Step 9.4: Install Laravel Boost**

Run: `composer boost` (alias for `vendor/bin/testbench boost:install`)
Expected: Boost installs into the workbench skeleton; follow its prompts. Verify the Boost MCP server config now references `vendor/bin/testbench artisan` (or equivalent) as the entry point.

- [ ] **Step 9.5: Add generated-but-noisy paths to `.gitignore`**

Append to `.gitignore`:
```
workbench/.env
workbench/bootstrap/cache/
workbench/storage/
```
(Do NOT gitignore the whole `workbench/` directory — contributors need the scaffold checked in.)

- [ ] **Step 9.6: Commit**

```bash
git add workbench testbench.yaml composer.json .gitignore
git commit -m "feat(dev): add testbench workbench skeleton and laravel/boost wiring"
```

---

## Chunk 5: Test Coverage Expansion (Unit Tests)

### Task 10: Add grammar-level unit tests that do NOT need a ClickHouse connection

**Files:**
- Create: `tests/Unit/QueryGrammarTest.php`
- Create: `tests/Unit/SchemaGrammarTest.php`

> Why a separate tier: the existing tests are all integration tests that hit ClickHouse. Pure SQL-generation tests give us a fast feedback loop that CI can run without spinning up Docker.

- [ ] **Step 10.1: Failing test for `QueryGrammar::prepareParameters`**

```php
<?php

namespace Tests\Unit;

use PhpClickHouseLaravel\QueryGrammar;
use PHPUnit\Framework\TestCase;

class QueryGrammarTest extends TestCase
{
    public function test_prepare_parameters_replaces_named_bindings(): void
    {
        $sql = QueryGrammar::prepareParameters("SELECT * FROM t WHERE id = :id");
        // assert whatever prepareParameters is documented to produce
        $this->assertIsString($sql);
        $this->assertStringContainsString('id', $sql);
    }
}
```

- [ ] **Step 10.2: Run it**

Run: `vendor/bin/phpunit --filter QueryGrammarTest`
Expected: test passes if `prepareParameters` is a pass-through for this input; otherwise it documents the real behavior and we tighten the assertion.

- [ ] **Step 10.3: Read the real `QueryGrammar::prepareParameters` implementation**

Open `src/QueryGrammar.php` and write down what the method actually does in a comment inside the test. Replace the placeholder assertion with an exact-output assertion based on the real behavior. This is the **real** test — Step 10.1 was just scaffolding.

- [ ] **Step 10.4: SchemaGrammar — column type mapping**

```php
<?php

namespace Tests\Unit;

use PhpClickHouseLaravel\SchemaGrammar;
use PHPUnit\Framework\TestCase;

class SchemaGrammarTest extends TestCase
{
    public function test_type_string_maps_to_clickhouse_string(): void
    {
        $grammar = new SchemaGrammar();
        // Blueprint/Fluent column stub
        $column = (object) ['type' => 'string', 'name' => 'f', 'length' => null];
        $this->assertSame('String', $grammar->typeString($column));
    }
}
```

Adjust to the real `SchemaGrammar` API after reading `src/SchemaGrammar.php` — this is a template, not a final test.

- [ ] **Step 10.5: Commit**

```bash
git add tests/Unit
git commit -m "test(unit): add grammar-level tests that do not require a clickhouse connection"
```

---

### Task 11: Builder SQL-generation tests (no DB)

**Files:**
- Create: `tests/Unit/BuilderSqlTest.php`

- [ ] **Step 11.1: Test `whereIn` + `whereBetween` + `orWhere` combinations**

Mirror the existing `BaseTest::testMultipleWheres` and `testOrWhere` but without ClickHouse — use a standalone `Builder` with a stub grammar and assert on `toSql()` only.

Read `tests/BaseTest.php::testMultipleWheres` for the exact expected string format (backtick-quoted identifiers, `IN ('zz')`, `BETWEEN 1 AND 2`). Mirror the shape.

- [ ] **Step 11.2: Run & commit**

```bash
vendor/bin/phpunit --filter BuilderSqlTest
git add tests/Unit/BuilderSqlTest.php
git commit -m "test(unit): add builder sql-generation tests"
```

---

## Chunk 6: CI Workflow (GitHub Actions)

### Task 12: Matrix workflow

**Files:**
- Create: `.github/workflows/tests.yml`

- [ ] **Step 12.1: Write the workflow**

```yaml
name: tests

on:
  push:
    branches: [master]
  pull_request:

jobs:
  phpunit:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ['8.5']
        laravel: ['^13.0']
        clickhouse: ['24.8']

    services:
      clickhouse01:
        image: clickhouse/clickhouse-server:${{ matrix.clickhouse }}
        ports: ['18123:8123']
      clickhouse02:
        image: clickhouse/clickhouse-server:${{ matrix.clickhouse }}
        ports: ['18124:8123']

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: none

      - name: Install dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" --no-update --no-interaction
          composer update --prefer-dist --no-interaction

      - name: Wait for ClickHouse
        run: |
          for i in {1..30}; do
            if curl -sf 'http://127.0.0.1:18123/?query=SELECT%201' > /dev/null; then
              echo "clickhouse01 ready"; break
            fi
            sleep 2
          done

      - name: Run PHPUnit
        run: vendor/bin/phpunit
```

> Note: the `services:` block in GitHub Actions does not support custom volume mounts, so the cluster-aware tests (`ClusterTest`) may need `markTestSkipped('cluster config requires docker-compose')` when running under CI. Prefer skipping in CI over complicating the workflow.

- [ ] **Step 12.2: Guard cluster tests behind env flag**

Edit `tests/ClusterTest.php` to add at the top of each test:
```php
if (! env('CLICKHOUSE_CLUSTER_AVAILABLE')) {
    $this->markTestSkipped('company_cluster config requires docker-compose services');
}
```

Locally, set `CLICKHOUSE_CLUSTER_AVAILABLE=1` in `phpunit.xml <php>` block to keep these tests running during dev.

- [ ] **Step 12.3: Commit**

```bash
git add .github/workflows/tests.yml tests/ClusterTest.php phpunit.xml
git commit -m "ci: run phpunit against real clickhouse services in github actions"
```

---

## Final Verification Checklist

- [ ] `composer install` from a clean clone succeeds.
- [ ] `vendor/bin/testbench list` prints artisan commands.
- [ ] `vendor/bin/testbench tinker --execute="dump(config('database.connections.clickhouse'));"` shows the merged config.
- [ ] With `docker compose -f docker-compose.test.yaml up -d`, `composer test` runs all tests green.
- [ ] `docs/howto_run_local_test.md` reflects the three-command flow.
- [ ] `workbench/` scaffold is checked in; `workbench/.env` and generated storage are gitignored.
- [ ] GitHub Actions workflow exists and runs on push/PR.
- [ ] Old `tests.bootstrap.sh` and `tests/CreatesApplication.php` are gone.
- [ ] `composer.json` has `extra.laravel.providers` + `require-dev` testbench/boost.