```shell
sudo sh -c "docker compose -f docker-compose.test.yaml run php sh /src/tests.bootstrap.sh; docker compose -f docker-compose.test.yaml down --remove-orphans -v"
```