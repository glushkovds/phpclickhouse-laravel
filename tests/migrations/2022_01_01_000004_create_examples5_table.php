<?php

use PhpClickHouseSchemaBuilder\Expression;
use PhpClickHouseSchemaBuilder\Tables\MergeTree;

return new class extends \PhpClickHouseLaravel\Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        static::createMergeTree('examples5', fn(MergeTree $table) => $table
            ->columns([
                $table->datetime('created_at', 3)->default(new Expression('now64()')),
                $table->int64('f_int'),
                $table->string('f_string'),
                $table->bool('f_bool'),
            ])
            ->orderBy('f_int')
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        static::write('DROP TABLE examples5');
    }
};
