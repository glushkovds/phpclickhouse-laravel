<?php

return new class extends \PhpClickHouseLaravel\Migration {

    protected $connection = 'clickhouse-cluster';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! env('CLICKHOUSE_CLUSTER_AVAILABLE')) {
            // Skip: the clickhouse-cluster connection and {replica}/{shard} macros
            // are only configured when docker-compose.test.yaml mounts
            // tests/docker/clickhouse0*/config.xml. GH Actions service containers
            // cannot mount those, so this migration would fail there.
            return;
        }

        static::write(
            "
            CREATE TABLE IF NOT EXISTS examples4 (
                created_at DateTime64 DEFAULT now64(),
                f_int Int64,
                f_string String
            )
            ENGINE = ReplicatedMergeTree('/clickhouse/tables/default.examples4', '{replica}')
            ORDER BY (f_int)
        "
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! env('CLICKHOUSE_CLUSTER_AVAILABLE')) {
            return;
        }
        static::write('DROP TABLE examples4');
    }
};
