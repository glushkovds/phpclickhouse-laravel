# Running tests locally

PHP runs on the host (via Orchestra Testbench). Only ClickHouse + Zookeeper
live in containers.

Prerequisites:

- PHP 8.5 with Composer
- A Docker-compatible runtime (Docker Desktop, Colima, etc.) with
  `docker compose` or `docker-compose`.

Steps:

1. `docker compose -f docker-compose.test.yaml up -d`
   (or `docker-compose -f docker-compose.test.yaml up -d` on standalone compose)
2. `composer install`
3. `composer test` (alias for `vendor/bin/phpunit`)

Tear down: `docker compose -f docker-compose.test.yaml down -v`

## Cluster tests

`ClusterTest` needs the `company_cluster` definition that lives in
`tests/docker/clickhouse01/config.d/*.xml`, mounted into the ClickHouse
containers by `docker-compose.test.yaml`. Locally this runs by default
because `phpunit.xml` sets `CLICKHOUSE_CLUSTER_AVAILABLE=1`.

In CI, GitHub Actions service containers cannot mount these configs,
so `.github/workflows/tests.yml` overrides the env var to `0` and the
two cluster tests `markTestSkipped`.

## Artisan and Laravel Boost

`vendor/bin/testbench <artisan-command>` gives you a full Laravel console
against the package, e.g. `vendor/bin/testbench tinker`,
`vendor/bin/testbench migrate`, or `vendor/bin/testbench list`.

Laravel Boost's MCP server is wired via `composer boost` (re-runs the
installer). The generated `.mcp.json`, `AGENTS.md`, `CLAUDE.md`, and
`.junie/mcp/mcp.json` are committed.
