#!/bin/bash

# Installing current library files to empty laravel app
cp -r /src/* vendor/glushkovds/phpclickhouse-laravel

# Preparing Phpunit
cp /src/phpunit.xml phpunit.xml
rm -rf /app/tests/Feature
rm -rf /app/tests/Unit
cp -r /src/tests/* /app/tests

# Configuring environment
cp /src/tests/config/database.php /app/config/database.php
cp /src/tests/config/app.php /app/config/app.php
cp /src/tests/migrations/exampleTable.php /app/database/migrations/2022_01_01_000000_example.php
cp /src/tests/migrations/example2Table.php /app/database/migrations/2022_01_01_000001_example.php
cat /src/tests/config/.env >> /app/.env

# Running tests
php artisan test
