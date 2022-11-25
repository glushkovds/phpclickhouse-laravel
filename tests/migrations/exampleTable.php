<?php

return new class extends \PhpClickHouseLaravel\Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        static::write(
            "
            CREATE TABLE examples (
                created_at DateTime64 DEFAULT now64(),
                f_int Int64,
                f_string String
            )
            ENGINE = MergeTree()
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
        static::write('DROP TABLE examples');
    }
};
