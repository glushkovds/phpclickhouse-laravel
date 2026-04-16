## Running tests locally

1. `docker compose -f docker-compose.test.yaml up -d` (or `docker-compose -f docker-compose.test.yaml up -d` on standalone compose)
2. `composer install`
3. `composer test` (or `vendor/bin/phpunit`)

To tear down: `docker compose -f docker-compose.test.yaml down -v`
