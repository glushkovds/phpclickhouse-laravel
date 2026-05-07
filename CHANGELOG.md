# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-05-07

### Added

- In-memory buffered inserts on `BaseModel`: `buffer()` accumulates rows per-model and `flushBuffer()` sends them to ClickHouse as a single `insertAssocBulk` HTTP request. The `$casts` pipeline used by `insertAssoc()` is reused, so cast behavior is identical. On flush failure the buffer is preserved so the caller can retry.
- Helper methods alongside the buffer API: `bufferCount()`, `getBufferedRows()`, `clearBuffer()`, and `BaseModel::flushAllBuffers()`.
- Auto-flush on script shutdown: the service provider registers a Laravel `terminating` callback plus a `register_shutdown_function` fallback for non-HTTP scripts. Errors during auto-flush are reported via `report()` rather than thrown.

### Changed

- Extracted the row-normalization + cast loop from `insertAssoc()` into a shared `prepareAssocRowsForInsert()` helper so manual and buffered inserts go through the same path. Public behavior of `insertAssoc()` is unchanged.

## [1.1.0] - 2026-04-17

### Added

- Package auto-discovery — the service provider registers itself via `extra.laravel.providers`, so no manual entry in `config/app.php` / `bootstrap/providers.php` is needed.
- Multi-connection support in the publishable `config/clickhouse.php`. Each entry is merged into `config('database.connections.<name>')`; user-supplied values always win.
- Unit-level test coverage for `QueryGrammar`, `SchemaGrammar`, and `Builder` SQL generation — these run without Docker / ClickHouse.
- GitHub Actions CI workflow against real ClickHouse service containers, with cluster tests gated off in CI.

### Changed

- `fix_default_query_builder` is enabled by default in the shipped config.
- Development flow migrated to **Orchestra Testbench** + `workbench/` skeleton. `vendor/bin/testbench <artisan-command>` works in the repo root, and **Laravel Boost** is wired via `composer boost`.
- Replaced the `tests.bootstrap.sh` + `bitnami/laravel` container flow with a slimmed-down `docker-compose.test.yaml` that only runs ClickHouse + Zookeeper.

### Removed

- Legacy `.travis.yml` and `.github/workflows/test.yml`.

## [1.0.0] - 2026-04-09

### Changed

- Forked from [glushkovds/phpclickhouse-laravel](https://github.com/glushkovds/phpclickhouse-laravel) at 2.5.2.
- Minimum PHP 8.5, Laravel 13+ only.

[1.2.0]: https://github.com/oralunal/phpclickhouse-laravel/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/oralunal/phpclickhouse-laravel/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/oralunal/phpclickhouse-laravel/releases/tag/v1.0.0
