# Changelog

## 1.1.0 [2026-04-17]

### Features
1. Package auto-discovery — the service provider registers itself via `extra.laravel.providers`, so no manual entry in `config/app.php` / `bootstrap/providers.php` is needed.
2. Publishable `config/clickhouse.php` now supports multiple connections. Each entry is merged into `config('database.connections.<name>')`; user-supplied values always win.
3. `fix_default_query_builder` is enabled by default in the shipped config.
4. Development flow migrated to **Orchestra Testbench** + `workbench/` skeleton. `vendor/bin/testbench <artisan-command>` works in the repo root, and **Laravel Boost** is wired via `composer boost`.
5. Added unit-level test coverage for `QueryGrammar`, `SchemaGrammar`, and `Builder` SQL generation — these run without Docker / ClickHouse.
6. GitHub Actions CI workflow against real ClickHouse service containers, with cluster tests gated off in CI.

### Internal
- Replaced the `tests.bootstrap.sh` + `bitnami/laravel` container flow with a slimmed-down `docker-compose.test.yaml` that only runs ClickHouse + Zookeeper.
- Removed the legacy `.travis.yml` and `.github/workflows/test.yml`.

## 1.0.0 [2026-04-09]

Forked from [glushkovds/phpclickhouse-laravel](https://github.com/glushkovds/phpclickhouse-laravel) at 2.5.2.

### Changes from upstream
- Minimum PHP 8.5, Laravel 13+ only.
