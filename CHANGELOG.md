## 1.19.0 [2023-09-28]

### Features
1. Added column casting feature for data insertion

## 1.18.0 [2023-07-10]

### Features
1. Added the settings clause to the select query builder

## 1.17.0 [2023-03-24]

### Features
1. Working with multiple Clickhouse instances in a project

## 1.16.3 [2023-03-03]

### Bug fixes
1. Laravel/Lumen 10+ support

## 1.16.2 [2023-03-03]

### Bug fixes
1. Quoted partition name in "optimize table" statement

## 1.16.1 [2023-01-30]

### Bug fixes
1. Fix complying with psr-4 autoload standard \PhpClickHouseLaravel\Exceptions\QueryException

## 1.16 [2022-12-27]

### Features
1. Added update rows ability

## 1.15 [2022-08-15]

### Features
1. Add method BaseModel::truncate

## 1.14 [2022-06-29]

### Features
1. Add simple events
1. Add PHP 8 support

## 1.12.0 [2022-01-19]

### Features
1. Add delete method for BaseModel

## 1.11.0 [2022-01-09]

### Fixed
1. Fix issue #8: Migrations on standalone Clickhouse database

## 1.10.0 [2021-09-20]

### Features
1. Ability to set connection settings

## 1.9.0 [2021-08-25]

### Features
1. Lumen support
1. Add InsertArray expression

## 1.8.0 [2021-06-07]

### Features
1. Add BaseModel::$tableForInserts for Buffer engine

## 1.7.0 [2021-05-24]

### Features
1. Add method Builder::chunk

## 1.6.0 [2021-03-25]

### Features
1. Add method BaseModel::optimize

## 1.5.0 [2020-12-18]

### Features
1. Fix restriction: "keys inside the array list must match" at \ClickHouseDB\Client::prepareInsertAssocBulk

## 1.4.0 [2020-12-11]

### Features
1. [#1](https://github.com/glushkovds/phpclickhouse-laravel/pull/1):  Ability to retry requests while received not 200 response, maybe due network connectivity problems.
