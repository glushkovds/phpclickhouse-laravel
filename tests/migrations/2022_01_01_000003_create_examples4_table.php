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
        static::write('DROP TABLE examples4');
    }
};
